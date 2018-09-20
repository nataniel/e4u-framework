<?php
namespace E4u\Common\Collection;

use E4u\Request;

interface Criteria
{
    public function __construct($options = []);
    public function isEmpty();
    public function getSortField();
    public function getSortOrder();
    public function setSortBy($orderBy);
    public function getSortBy();
    public function isValidSortOption($orderBy);
    public function toUrl();
    public static function fromRequest(Request\Http $request);
}