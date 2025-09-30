<?php
// src/Service/LogParser/LogParserInterface.php

namespace App\Service\LogParser;

interface LogParserInterface
{
    public function supports(string $content): bool;
    public function parse(string $content): array;
    public function getName(): string;
}
