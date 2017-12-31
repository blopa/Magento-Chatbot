<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
 *
 * This file is part of Werules/Chatbot.
 *
 * Werules/Chatbot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class Werules_Chatbot_WebhookController extends Mage_Core_Controller_Front_Action{
//    public function IndexAction() {
//
//      $this->loadLayout();
//      $this->getLayout()->getBlock("head")->setTitle($this->__("Titlename"));
//            $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
//      $breadcrumbs->addCrumb("home", array(
//                "label" => $this->__("Home Page"),
//                "title" => $this->__("Home Page"),
//                "link"  => Mage::getBaseUrl()
//           ));
//
//      $breadcrumbs->addCrumb("titlename", array(
//                "label" => $this->__("Titlename"),
//                "title" => $this->__("Titlename")
//           ));
//
//      $this->renderLayout();
//
//    }
    public function IndexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate("chatbot/index.phtml"); // use root block to output pure values without html tags
        $this->renderLayout();
    }

    public function MessengerAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate("chatbot/messenger.phtml"); // use root block to output pure values without html tags
        $this->renderLayout();
    }

    public function TelegramAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate("chatbot/telegram.phtml"); // use root block to output pure values without html tags
        //$this->getLayout()->getBlock('root')->setTemplate("werules_chatbot_view.phtml")->setTitle(Mage::helper('core')->__('Chatbot')); // use root block to output pure values without html tags
        //$this->getLayout()->getBlock('head')->setTitle(Mage::helper('core')->__('Chatbot'));
        //$this->getLayout()->getBlock('head')->setTitle($this->__('My Title')); // then this works
        //$this->getLayout()->unsetBlock('head');
        $this->renderLayout();
    }
}