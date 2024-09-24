<?php

use Joy2fun\DcatApiDoc\ApiParser;


/*
    [
      "name" => "_scope_"
      "label" => "my , latest 最近"
      "method" => "scope"
    ]
*/
it('can parse filters', function () {

    $parser = new ApiParser(code: file_get_contents(dirname(__DIR__) . ('/examples/SampleController.php')));
    $filters = collect($parser->getFilters())->keyBy('name');

    expect($filters->has('_scope_'))->toBeTrue('has _scope_');
});

/*
    [
      "name" => "gender"
      "label" => null
      "method" => "radio"
      "options" => """
        {\n
            "male": "男",\n
            "female": "女"\n
        }
        """
    ]
*/
it('can parse payloads', function () {
    config(['enums' => require dirname(__DIR__) . '/config/enums.php']);
    $parser = new ApiParser(code: file_get_contents(dirname(__DIR__) . ('/examples/SampleController.php')));
    $payloads = collect($parser->getPayloads())->keyBy('name');

    expect($payloads->has('gender'))->toBeTrue('has gender');
    expect($payloads->get('gender'))->toHaveKeys(['options'], 'gender contains key: options');
    expect($payloads->get('gender')['options'])->toBeJson('gender options is json from config("enums.gender")');

});
