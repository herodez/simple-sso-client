<?php


namespace Optime\SimpleSsoClientBundle\Security\User;


interface UserFactoryInterface
{
    /**
     * @param array $data
     * @return ExternalUserInterface
     */
   public function createFromRemoteData(array $data);
}