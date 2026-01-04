<?php

declare(strict_types=1);

namespace MCSets\command\form;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use MCSets\Loader;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MCSetsSettingsForm extends CustomForm
{
    public function __construct()
    {
        $title = "MCSets Settings";
        $elements = [
            new Label("desc", "Hello! You can change your MCSets plugin settings below."),
            new Input("token", "Put your token here!", "//TODO: put a example token here, like how long it would be with random ahh letters", Loader::getInstance()->getConfigManager()->getToken())
        ];

        $onSubmit = function (Player $player, CustomFormResponse $data): void {
            $token = $data->getString("token");
            //TODO: any checking needed here?

            Loader::getInstance()->setToken($token);

            $player->sendMessage(TextFormat::GREEN . "Successfully set your token to: " . TextFormat::YELLOW . $token);
        };
        parent::__construct($title, $elements, $onSubmit);
    }
}
