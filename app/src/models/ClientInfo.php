<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;

class ClientInfo extends DataObject
{
    private static $table_name = 'ClientInfo';
    
    private static $db = [
        'PartyType' => 'Varchar(100)', // Seller, Buyer, Landlord, Tenant
        'ClientType' => 'Enum("Individual,Entity")',
        'ActingType' => 'Text', // Full description of acting type
        'ClientActingTypeSelection' => 'Varchar(255)',
    ];
    
    private static $has_one = [
        'FormSubmission' => FormSubmission::class,
        'OwnershipProof' => File::class,
        'FormA1File'     => File::class,
        'FormA2File'     => File::class,
        'FormA3File'     => File::class,
        'FormA4File'     => File::class,
        'FormBFile'      => File::class,
    ];

    private static $owns = [
        'OwnershipProof',
        'FormA1File',
        'FormA2File',
        'FormA3File',
        'FormA4File',
        'FormBFile',
    ];
    
    private static $summary_fields = [
        'PartyType' => 'Party Type',
        'ClientType' => 'Client Type',
        'ActingType' => 'Acting Type',
    ];
    
    private static $searchable_fields = [
        'PartyType',
        'ClientType',
    ];
}