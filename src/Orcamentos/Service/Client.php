<?php

namespace Orcamentos\Service;

use Orcamentos\Model\Client as ClientModel;
use Intervention\Image\Image;
use Exception;
  
/**
 * Client Entity
 *
 * @category Orcamentos
 * @package Service
 * @author  Mateus Guerra<mateus@coderockr.com>
 */
class Client extends Service
{
    /**
     * Function that saves a new client
     *
     * @return                Function used to save a new Client
     */
    public function save($data, $logotype = null)
    {
        $data = json_decode($data);

        if (!isset($data->name) || !isset($data->responsable) || !isset($data->email) || !isset($data->companyId)) {
            throw new Exception("Invalid Parameters", 1);
        }

        $client = null;
        if ( isset($data->id) ) {
            $client = $this->em->getRepository("Orcamentos\Model\Client")->find($data->id);
        }

        if (!$client) {
            $client = new ClientModel();
        }

        $client->setName($data->name);
        $client->setResponsable($data->responsable);
        $client->setEmail($data->email);

        if (isset($data->cnpj)) {
            $client->setCnpj($data->cnpj);
        }
        if (isset($data->telephone)) {
            $client->setTelephone($data->telephone);
        }
        $company = $this->em->getRepository('Orcamentos\Model\Company')->find($data->companyId);
        
        if (!isset($company)) {
            throw new Exception("No company", 1);
        }
        $client->setCompany($company);

        if (isset($logotype)) {
            $originalName = $logotype->getClientOriginalName();
            $components = explode('.', $originalName);
            $fileName = md5(time()) . '.' . end($components);
            
            $file = Image::make($logotype->getPathName())->grab(80);

            $file->save("public/img/logotypes/" . $fileName );
            $client->setLogotype($fileName);
        }

        $this->em->persist($client);
        $this->em->flush();

        return $client;
    }


    /**
     * Function that searches clients
     *
     * @return                
     */
    public static function search($data)
    {
        $data = json_decode($data);

        if (!isset($data->query) || !isset($data->companyId)) {
            throw new Exception("Invalid Parameters", 1);
        }
        $company = $this->em->getRepository('Orcamentos\Model\Company')->find($data->companyId);

        $result = $this->em->getRepository("Orcamentos\Model\Client")->createQueryBuilder('c')
           ->where('c.company = :company')
           ->andWhere('c.name LIKE :query')
           ->setParameter('company', $company )
           ->setParameter('query', '%'. $data->query.'%')
           ->getQuery();

        $this->em->flush();

        return $result;
    }
}
