<?php

namespace yiiunit\extensions\ews;

use simialbi\yii2\ews\models\Folder;

class QueryBuilderTest extends TestCase
{
    public function testLimit()
    {
        $query = Folder::find()
            ->limit(10)
            ->offset(0);

        $this->assertEquals(10, $query->limit);
        $this->assertEquals(0, $query->offset);

        $command = $query->createCommand();
        $this->assertIsObject($command);

        $request = $command->getRequest();
        $this->assertObjectHasProperty('FractionalPageItemView', $request);

        $fractionalPageItemView = $request->FractionalPageItemView;
        $this->assertObjectHasProperty('MaxEntriesReturned', $fractionalPageItemView);
        $this->assertEquals(10, $fractionalPageItemView->MaxEntriesReturned);

        $this->assertObjectHasProperty('Denominator', $fractionalPageItemView);
        $this->assertEquals(10, $fractionalPageItemView->Denominator);

        $this->assertObjectHasProperty('Numerator', $fractionalPageItemView);
        $this->assertEquals(0, $fractionalPageItemView->Numerator);
    }
}
