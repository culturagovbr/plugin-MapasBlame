<?php

namespace MapasBlame;

class Controller extends \MapasCulturais\Controllers\EntityController
{
    use \MapasCulturais\Traits\ControllerAPI;

    protected $entityClassName = 'MapasBlame\Entities\Blame';

    function __construct()
    { 
    }
      
}
