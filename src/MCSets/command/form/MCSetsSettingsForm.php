<?php

declare(strict_types=1);

namespace MCSets\command\form;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use pocketmine\player\Player;

class MCSetsSettingsForm extends CustomForm
{
    //TODO: 

    public function __construct()
    {
        $title = "MCSets Settings";
        $elements = [];

        $onSubmit = function (Player $player, CustomFormResponse $data): void {};
        parent::__construct($title, $elements, $onSubmit);
    }
}
