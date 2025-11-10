<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Tests\Unit\Result;

use Honey\ODM\Meilisearch\Config\AsAttribute;
use Honey\ODM\Meilisearch\Config\AsDocument;
use Honey\ODM\Meilisearch\Config\ClassMetadataRegistry;
use Honey\ODM\Meilisearch\ObjectManager\ObjectManager;
use Honey\ODM\Meilisearch\Result\ObjectResultset;
use Meilisearch\Client;

it('stores metadata', function () {
    $documents = [
        [
            'id' => 1,
            'name' => 'Lille',
            '_geo' => [50.63, 3.06],
            '_vectors' => [
                'default' => [
                    [-1, 0, 1],
                ],
            ],
        ],
        [
            'id' => 2,
            'name' => 'Paris',
            '_geo' => [48.85, 2.29],
            '_vectors' => [
                'default' => [
                    [-2, 0, 2],
                ],
            ],
        ],
    ];

    $city = new class {
        public function __construct(
            #[AsAttribute(primary: true)]
            public ?int $id = null,
            #[AsAttribute]
            public ?string $name = null,
        ) {
        }
    };

    $objectManager = new ObjectManager(
        new Client('https://example.com:7700'),
        classMetadataRegistry: new ClassMetadataRegistry(configurations: [
            $city::class => new AsDocument('cities'),
        ]),
    );

    $objects = new ObjectResultset(
        $objectManager,
        $documents,
        $objectManager->classMetadataRegistry->getClassMetadata($city::class),
    );

    expect($objects[0])->toBeInstanceOf($city::class)
        ->and($objects[0]->id)->toBe(1)
        ->and($objects[0]->name)->toBe('Lille')
        ->and($objects->extra[$objects[0]]['geo'])->toBe([50.63, 3.06])
        ->and($objects->extra[$objects[0]]['vectors'])->toBe(['default' => [[-1, 0, 1]]])
        ->and($objects[1])->toBeInstanceOf($city::class)
        ->and($objects[1]->id)->toBe(2)
        ->and($objects[1]->name)->toBe('Paris')
        ->and($objects->extra[$objects[1]]['geo'])->toBe([48.85, 2.29])
        ->and($objects->extra[$objects[1]]['vectors'])->toBe(['default' => [[-2, 0, 2]]]);
});
