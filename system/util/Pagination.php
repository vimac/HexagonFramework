<?php

namespace Hexagon\system\util;

class Pagination {

    public $pageSize = 10;
    public $totalRecord = 0;
    public $extraParam = [];
    public $template = '';
    public $display = 10;

    public $totalPage = NULL;
    public $currentPage = NULL;
    public $lastPage = NULL;
    public $nextPage = NULL;
    public $prevPage = NULL;
    public $firstPage = NULL;

    public $pagination;

    /**
     * @param string $template
     * @param int $currentPage
     * @param int $pageSize
     * @param int $totalRecord
     * @param array $extraParam
     * @param string $firstPageUrl
     * @param int $display
     */
    public function __construct($template, $currentPage, $pageSize, $totalRecord, $extraParam = [], $firstPageUrl = NULL, $display = 10) {
        $this->currentPage = $currentPage;
        $this->pageSize = $pageSize;
        $this->totalRecord = $totalRecord;
        $this->extraParam = $extraParam;
        $this->display = $display;

        $extraParam['pageSize'] = $pageSize;

        foreach ($extraParam as $k => $v) {
            $template = str_replace('(' . $k . ')', $v, $template);
        }
        $this->template = $template;

        $totalPage = intval(ceil($totalRecord / $pageSize));
        $this->totalPage = $totalPage;

        $pagination = [];

        if ($totalPage > 0) {
            $left = ceil($display / 2);
            $right = ceil($display / 2) + 1;

            for ($i = 0; $i < $left; $i++) {
                $k = $currentPage - $i;
                if ($k > 1) {
                    $pagination[$k] = str_replace('(page)', $k, $template);
                } elseif ($k === 1) {
                    if ($firstPageUrl) {
                        $pagination[$k] = $firstPageUrl;
                    } else {
                        $pagination[$k] = str_replace('(page)', 1, $template);
                    }
                } else {
                    break;
                }
            }

            for ($i = 1; $i < $right; $i++) {
                $k = $currentPage + $i;
                if ($k <= $totalPage) {
                    $pagination[$k] = str_replace('(page)', $k, $template);
                } else {
                    break;
                }
            }

            ksort($pagination);

            if (array_key_exists($currentPage + 1, $pagination)) {
                $this->nextPage = $pagination[$currentPage + 1];
            }
            if (array_key_exists($currentPage - 1, $pagination)) {
                $this->prevPage = $pagination[$currentPage - 1];
            }

            if (array_key_exists(1, $pagination)) {
                $this->firstPage = $pagination[1];
            } else {
                if ($firstPageUrl) {
                    $this->firstPage = $firstPageUrl;
                } else {
                    $this->firstPage = str_replace('(page)', 1, $template);
                }
            }

            if (array_key_exists($totalPage, $pagination)) {
                $this->lastPage = $pagination[$totalPage];
            } else {
                if ($totalPage === 1) {
                    $this->lastPage = $this->firstPage;
                } else {
                    $this->lastPage = str_replace('(page)', $totalPage, $template);
                }
            }
        }

        $this->pagination = $pagination;
    }

    public function getPagination() {
        return $this->pagination;
    }

    public function needPage() {
        return $this->totalPage > 0;
    }

}