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

    public function getParser()
    {
        if (! $this->parser) {
            $this->parser = (new ParserFactory)->createForHostVersion();
        }

        return $this->parser;
    }

    public function getFilters($class)
    {
        $parser = $this->getParser();
        $code = ($this->getClassSourceCode($class));

        $columns = [];
        $scopes = [];

        try {
            $nodes = $parser->parse($code);
            $grid = $this->findMethod($nodes, 'grid');
            $filters = $this->findMethodCalls([$grid], ['equal', 'like', 'between', 'scope']);
            foreach ($filters as $filter) {
                if ($filter->name->name == 'scope') {
                    $scopes[] = sprintf("%s %s", ($filter->args[0] ?? null)?->value->value, ($filter->args[1] ?? null)?->value->value);
                } else {
                    $columns[] = [
                        'name' => ($filter->args[0] ?? null)?->value->value,
                        'label' => ($filter->args[1] ?? null)?->value->value,
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
            ];
        }

        return $columns;
    }

    public function findMethod($nodes, $name)
    {
        $nodeFinder = new NodeFinder;
        $method = $nodeFinder->findFirst($nodes, function (Node $node) use ($name) {
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
