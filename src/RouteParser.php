<?php

namespace Sodaho\ApiRouter;

/**
 * Class RouteParser
 *
 * Handles the conversion of a route string with placeholders (e.g., '/user/{id:\d+}')
 * into a regular expression.
 */
class RouteParser
{
    // This regex finds placeholders like {name} or {id:\d+}
    private const VARIABLE_REGEX = <<<'REGEX'
        /\{
            \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s* # The variable name
            (?:
                : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*) # The optional regex pattern
            )?
        \}/x
        REGEX;

    /**
     * Parses a route string into a regex and a list of variable names.
     *
     * @param string $route The route string to parse.
     * @return array{0: string, 1: list<string>} An array containing the regex pattern and the ordered variable names.
     */
    public function parse(string $route): array
    {
        $variableNames = [];
        $regexRoute = preg_replace_callback(self::VARIABLE_REGEX, function ($match) use (&$variableNames) {
            $variableNames[] = $match[1]; // e.g., 'id'
            $regex = $match[2] ?? '[^/]+'; // e.g., '\d+' or default
            return '(' . $regex . ')';
        }, $route);

        return [$regexRoute, $variableNames];
    }
}
