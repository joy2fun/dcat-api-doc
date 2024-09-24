<?php

namespace Joy2fun\DcatApiDoc;

use Exception;
use ReflectionClass;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\{Node, NodeFinder};
use ReflectionException;

class ApiParser
{
    protected $parser;
    protected $nodes;

    public function __construct(array $nodes = [], $code = null)
    {
        $this->setNodes($nodes);
        if ($code) {
            $this->setNodes($this->getParser()->parse($code));
        }
    }

    public function setNodes(array $nodes)
    {
        $this->nodes = $nodes;
    }

    public function getParser(): \PhpParser\Parser
    {
        if (! $this->parser) {
            $this->parser = (new ParserFactory)->createForHostVersion();
        }

        return $this->parser;
    }

    public function getFilters()
    {
        $columns = [];
        $scopes = [];

        try {
            $grid = $this->findMethod('grid');
            $filters = $this->findMethodCalls([$grid], ['equal', 'like', 'between', 'scope']);
            foreach ($filters as $filter) {
                if ($filter->name->name == 'scope') {
                    $scopes[] = sprintf("%s %s", ($filter->args[0] ?? null)?->value->value, ($filter->args[1] ?? null)?->value->value);
                } else {
                    $columns[] = [
                        'name' => ($filter->args[0] ?? null)?->value->value,
                        'label' => ($filter->args[1] ?? null)?->value->value,
                        'method' => $filter->name->name,
                    ];
                }
            }
        } catch (Error $e) {
            // echo 'Parse Error: ', $e->getMessage();
        }

        if ($scopes) {
            $columns[] = [
                'name' => '_scope_',
                'label' => implode(', ', $scopes),
                'method' => 'scope',
            ];
        }

        return $columns;
    }

    public function getPayloads()
    {
        $columns = [];
        $lastOption = null;

        try {
            $from = $this->findMethod('form');
            $fields = $this->findMethodCalls([$from], [
                'text', 'select', 'radio', 'number',
                'options',
            ]);

            foreach ($fields as $field) {
                if ($field->name->name == 'options') {
                    $prettyPrinter = new \PhpParser\PrettyPrinter\Standard;
                    // config('enums.x')
                    if($field->args[0]?->value?->name?->name ?? '' == 'config') {
                        $configKey = trim($prettyPrinter->prettyPrint($field->args[0]->value->args), '"\'');
                        $lastOption = json_encode(config($configKey, $configKey), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    } else {
                        $lastOption = $prettyPrinter->prettyPrint($field->args);
                    }
                    continue;
                }
                $tmp = [
                    'name' => ($field->args[0] ?? null)?->value->value,
                    'label' => ($field->args[1] ?? null)?->value->value,
                    'method' => $field->name->name,
                ];

                if ($lastOption && in_array($tmp['method'], ['radio', 'select'])) {
                    $tmp['options'] = $lastOption;
                    $lastOption = null;
                }

                $columns[] = $tmp;
            }
        } catch (Error $e) {
            // echo 'Parse Error: ', $e->getMessage();
        }

        return $columns;
    }

    public function findMethod($name)
    {
        $nodeFinder = new NodeFinder;
        $method = $nodeFinder->findFirst($this->nodes, function (Node $node) use ($name) {
            return $node instanceof \PhpParser\Node\Stmt\ClassMethod
                && $node->name->toString() === $name;
        });
        return $method;
    }

    public function findMethodCalls($nodes, $name)
    {
        $nodeFinder = new NodeFinder;
        $name = is_scalar($name) ? [$name] : $name;
        $methods = $nodeFinder->find($nodes, function (Node $node) use ($name) {
            return $node instanceof \PhpParser\Node\Expr\MethodCall
                && in_array($node->name->toString(), $name);
        });
        return $methods;
    }

    public function getClassSourceCode($className)
    {
        try {
            $reflection = new ReflectionClass($className);
            $filename = $reflection->getFileName();

            if ($filename === false) {
                throw new \Exception("Unable to retrieve source code for class {$className}.");
            }

            $content = file_get_contents($filename);

            return $content;
        } catch (ReflectionException $e) {
            throw new Exception("Class {$className} does not exist.");
        }
    }
}
