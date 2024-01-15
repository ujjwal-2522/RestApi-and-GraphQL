<?php
namespace CharlotteTilbury\RuleApi\Model;

use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Custom
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * Custom constructor.
     *
     * @param CollectionFactory $ruleCollectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->collectionFactory     = $collectionFactory;
        $this->logger         = $logger;
        $this->ruleRepository = $ruleRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPost($ruleId)
    {
        $product=null;
        $array =[];
        if ($ruleId === 0 || $ruleId === "") {
            return "please enter a valid id";
        }
        $rule = $this->ruleRepository->getById($ruleId);

        //echo $rule->getName();
        array_push($array,array('rule_name' => $rule->getName()));
        $salesRule = $this->collectionFactory->create()
            ->addFieldToFilter('rule_id',['eq' => $ruleId])
            ->addFieldToSelect('rule_id')
            ->join(
                ['acp' => 'acq_salesrule_product'],
                'main_table.rule_id = acp.rule_id AND  acp.website_id = "1" AND acp.condition_type = "condition"',
                ['product_id']
            );

        foreach ($salesRule->getData() as $subArray) {
            $product       = $this->productRepository->getById($subArray['product_id']);
            $discountPrice = $this->calculate($rule, $product);
            $array[]       = [
                'rule_name' => $rule->getName(),
                'sku' => $product->getSku(),
                'Name' => $product->getName(),
                'Price' => $product->getPrice(),
                "discount_price" => $discountPrice
            ];
        }

        return $array;
    }
    public static function calculate($rule, $product)
    {
        try {
            $simp_action  = $rule->getSimpleAction();
            $dis_Amount   = $rule->getDiscountAmount();
            $productPrice = $product->getPrice();
            switch ($simp_action) {
                case 'to_fixed':
                    $updatedPrice = min($dis_Amount, $productPrice);
                    break;
                case 'to_percent':
                    $updatedPrice = $productPrice * $dis_Amount / 100;
                    break;
                case 'by_fixed':
                    $updatedPrice = max(0, $productPrice - $dis_Amount);
                    break;
                case 'by_percent':
                    $updatedPrice = $productPrice * (1 - $dis_Amount / 100);
                    break;
                default:
                    $updatedPrice = 0;
            }

            return (string) $updatedPrice;
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}
