<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3f10f8314eb5ebdd4f9037d66a83d228
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
        'G' => 
        array (
            'GatewayWorker\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
        'GatewayWorker\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/gateway-worker/src',
        ),
    );

    public static $classMap = array (
        'SebastianBergmann\\Diff\\Chunk' => __DIR__ . '/..' . '/sebastian/diff/src/Chunk.php',
        'SebastianBergmann\\Diff\\Diff' => __DIR__ . '/..' . '/sebastian/diff/src/Diff.php',
        'SebastianBergmann\\Diff\\Differ' => __DIR__ . '/..' . '/sebastian/diff/src/Differ.php',
        'SebastianBergmann\\Diff\\LCS\\LongestCommonSubsequence' => __DIR__ . '/..' . '/sebastian/diff/src/LCS/LongestCommonSubsequence.php',
        'SebastianBergmann\\Diff\\LCS\\MemoryEfficientImplementation' => __DIR__ . '/..' . '/sebastian/diff/src/LCS/MemoryEfficientLongestCommonSubsequenceImplementation.php',
        'SebastianBergmann\\Diff\\LCS\\TimeEfficientImplementation' => __DIR__ . '/..' . '/sebastian/diff/src/LCS/TimeEfficientLongestCommonSubsequenceImplementation.php',
        'SebastianBergmann\\Diff\\Line' => __DIR__ . '/..' . '/sebastian/diff/src/Line.php',
        'SebastianBergmann\\Diff\\Parser' => __DIR__ . '/..' . '/sebastian/diff/src/Parser.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3f10f8314eb5ebdd4f9037d66a83d228::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3f10f8314eb5ebdd4f9037d66a83d228::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3f10f8314eb5ebdd4f9037d66a83d228::$classMap;

        }, null, ClassLoader::class);
    }
}
