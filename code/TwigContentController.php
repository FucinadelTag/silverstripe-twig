<?php

require_once __DIR__ . '/TwigControllerTrait.php';

class TwigContentController extends ContentController
{

    use TwigControllerTrait;

    protected $dic;

    public function __construct($dataRecord = null)
    {
        $this->dic = new TwigContainer;
        parent::__construct($dataRecord);
    }

}
