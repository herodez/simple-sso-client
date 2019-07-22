<?php


namespace Optime\SimpleSsoClientBundle\Security\User;

use Optime\SimpleSsoClientBundle\Security\User\BasicExternalUser;

class BasicUserFactory implements UserFactoryInterface
{
    /**
     * @param array $data
     * @param ExternalUserInterface
     */
    public function createFromRemoteData(array $data)
    {
        $this->checkResponseData($data);
        return BasicExternalUser::createFromArray($data);
    }
    
    /**
     * @param $data
     * @return mixed
     */
    protected function checkResponseData($data)
    {
        if (!isset($data['user']['username'])) {
            throw new AuthenticationCredentialsNotFoundException("Se esperaba el indice ['user']['username'] en la respuesta");
        }
        
        if (!isset($data['security_roles'])) {
            throw new AuthenticationCredentialsNotFoundException("Se esperaba el indice ['security_roles'] en la respuesta");
        }
    }
}