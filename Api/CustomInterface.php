<?php
namespace CharlotteTilbury\RuleApi\Api;

interface CustomInterface
{
    /**
     * Get post
     *
     * @param int $ruleId
     * @return array
     */
    public function getPost($ruleId);
}
