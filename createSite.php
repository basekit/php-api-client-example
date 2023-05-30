<?php
require_once __DIR__ . '/vendor/autoload.php';

use BaseKit\Api\ClientFactory;
use RandomLib\Factory;

$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->load();

$client = ClientFactory::create(
    [
        'base_uri' => $_ENV['REST_API_URL'],
        'username' => $_ENV['REST_API_USERNAME'],
        'password' => $_ENV['REST_API_PASSWORD'],
    ],
);

echo "Creating account and site\n";

$generator = (new Factory)->getMediumStrengthGenerator();
$password = $generator->generateString(15);
$username = 'user' . time();
$createAccountHolder = $client->getCommand(
    'CreateUser',
    [
        'brandRef' => 1,
        'firstName' => 'first',
        'lastName' => 'lastname',
        'username' => $username,
        'email' => $username . '@example.com',
        'password' => $password,
        'languageCode' => 'en',
        'entryFlowComplete' => 1, //This skips site onboarding which would reset all site content on first login
    ]
);
$response = $client->execute($createAccountHolder);
// accountHolderRef and userId/Ref are used interchangeably in our documentation and api endpoints
$accountHolderRef = $response['response']['accountHolder']['ref'];
echo "  Created account holder $accountHolderRef\n";

$createPackage = $client->getCommand(
    'AddPackage',
    [
        'billingFrequency' => 1,
        'packageRef' => 19,
        'userRef' => $accountHolderRef,
    ]
);
$response = $client->execute($createPackage);
echo "  Added package to account holder\n";

$createSite = $client->getCommand(
    'CreateSite',
    [
        'accountHolderRef' => $accountHolderRef,
        'brandRef' => 1,
        'domain' => "$username.dev.basekit.technology",
        'siteType' => 'responsive',
    ]
);
$response = $client->execute($createSite);
$siteRef = $response['response']['site']['ref'];
echo "  Created site $siteRef\n";

$getProfiles = $client->getCommand(
    'GetUsersProfiles',
    [
        'userRef' => $accountHolderRef,
    ]
);
$response = $client->execute($getProfiles);
$profileRef = $response['response']['profiles'][0]['ref'];

$fields = [
    [
        'name' => 'business',
        'value' => 'The Coffee Hut',
    ],
    [
        'name' => 'strapline',
        'value' => 'Serving the best coffee across Bristol',
    ],
];
$updateProfile = $client->getCommand(
    'UpdateProfile',
    [
        'userRef' => $accountHolderRef,
        'profileRef' => $profileRef,
        'fields' => $fields,
    ]
);
$response = $client->execute($updateProfile);

$getPages = $client->getCommand(
    'GetSitesPages',
    [
        'siteRef' => $siteRef,
    ],
);
$response = $client->execute($getPages);
$pageRef = $response['response']['pages'][0]['ref'];
echo "  Found home page ref $pageRef\n";

$getSections = $client->getCommand(
    'GetSections',
    [
        'siteRef' => $siteRef,
        'pageRef' => $pageRef,
    ],
);
$response = $client->execute($getSections);
$sections = $response['response']['sections'];

//Delete the default sections from home page
foreach($sections as $section) {
    $deleteSection = $client->getCommand(
        'DeleteSection',
        [
            'siteRef' => $siteRef,
            'pageRef' => $pageRef,
            'sectionRef' => $section['ref'],
        ]
    );
    $response = $client->execute($deleteSection);
}

//Add the image_text_2a section
echo "    Adding section image_text_2a\n";
$addSection = $client->getCommand(
    'AddSection',
    [
        'siteRef' => $siteRef,
        'pageRef' => $pageRef,
        'template' => 'image_text_2a',
    ],
);
$response = $client->execute($addSection)['response']['section'];
$sectionRef = $response['ref'];
$widgetsAdded = $response['widgets'];

//Upload an image for use in the new section
$getUploadToken = $client->getCommand(
    'CreateUploadToken',
    [
        'userRef' => $accountHolderRef,
    ]
);
$response = $client->execute($getUploadToken);
$uploadToken = $response['response']['token'];
echo "    Got upload token $uploadToken\n";

$file = fopen('./press.jpeg', 'r');
$uploadAsset = $client->getCommand(
    'CreateAsset',
    [
        'userRef' => $accountHolderRef,
        'siteRef' => $siteRef,
        'token' => $uploadToken,
        'file' => $file,
    ]
);
$response = $client->execute($uploadAsset);
$url = $response['response']['result']['url'];
echo "    Got new asset url $url\n";

//Update the widget text & images in the newly added section
foreach ($widgetsAdded as $widgetAdded) {
    echo "    Updating widget ref {$widgetAdded['ref']}\n";
    $updateSectionWidget = $client->getCommand(
        'UpdateSectionWidget',
        [
            'siteRef' => $siteRef,
            'pageRef' => $pageRef,
            'sectionRef' => $sectionRef,
            'widgetRef' => $widgetAdded['ref'],
            'headingContent' => 'My New Widget Content',
            'imageSrc' => $url,
        ],
    );
    $client->execute($updateSectionWidget);
}

//Add a contact_3a section above the first section we added
echo "    Adding section contact_3a\n";
$addSection = $client->getCommand(
    'AddSection',
    [
        'siteRef' => $siteRef,
        'pageRef' => $pageRef,
        'template' => 'contact_3a',
        'siblingRef' => $sectionRef,
        'position' => 'above',
    ],
);
$client->execute($addSection);

echo "  Adding a new page\n";
$addPage = $client->getCommand(
    'CreateSitePage',
    [
        'siteRef' => $siteRef,
        'pageUrl' => 'about',
        'type' => 'page',
        'title' => 'About Us',
        'status' => 'active',
        'menu' => 1,
        'seo_title' => 'About Us',
    ],
);
$response = $client->execute($addPage);

echo "Done\n";
