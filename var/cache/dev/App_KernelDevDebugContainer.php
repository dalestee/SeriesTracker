<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\ContainerZlDxCqW\App_KernelDevDebugContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/ContainerZlDxCqW/App_KernelDevDebugContainer.php') {
    touch(__DIR__.'/ContainerZlDxCqW.legacy');

    return;
}

if (!\class_exists(App_KernelDevDebugContainer::class, false)) {
    \class_alias(\ContainerZlDxCqW\App_KernelDevDebugContainer::class, App_KernelDevDebugContainer::class, false);
}

return new \ContainerZlDxCqW\App_KernelDevDebugContainer([
    'container.build_hash' => 'ZlDxCqW',
    'container.build_id' => '47a5658c',
    'container.build_time' => 1704706888,
    'container.runtime_mode' => \in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) ? 'web=0' : 'web=1',
], __DIR__.\DIRECTORY_SEPARATOR.'ContainerZlDxCqW');