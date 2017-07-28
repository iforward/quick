<?php

namespace app\modules\test;

class Module extends \q\base\Module
{
    public $controllerNamespace = 'app\modules\test\controllers';

    public function init()
    {
        parent::init();

//        $this->modules = [
//            'admin' => [
//                // 此处应考虑使用一个更短的命名空间
//                'class' => 'app\modules\test\modules\admin\Module',
//            ],
//        ];
        // custom initialization code goes here
    }
}


?>
