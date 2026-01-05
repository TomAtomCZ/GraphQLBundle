<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Youshido\GraphQLBundle\Command\GraphQLConfigureCommand;
use Youshido\GraphQLBundle\Execution\Container\SymfonyContainer;
use Youshido\GraphQLBundle\Security\Voter\BlacklistVoter;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $services = $container->services();

    /*
     * PARAMETERS
     */
    $parameters->set('graphql.processor.class', Youshido\GraphQLBundle\Execution\Processor::class);
    $parameters->set('graphql.execution_context.class', Youshido\GraphQLBundle\Execution\Context\ExecutionContext::class);
    $parameters->set('graphql.security_manager.class', Youshido\GraphQLBundle\Security\Manager\DefaultSecurityManager::class);

    $services
        ->defaults()
        ->autowire(false)
        ->autoconfigure(false);

    $services
        ->set('graphql.schema')
        ->synthetic()
        ->public();

    $services
        ->set('graphql.symfony_container_bridge', SymfonyContainer::class)
        ->call('setContainer', [service('service_container')]);

    $services
        ->set('graphql.execution_context', param('graphql.execution_context.class'))
        ->arg(0, service('graphql.schema'))
        ->call('setContainer', [service('graphql.symfony_container_bridge')]);

    $services
        ->set('graphql.processor', param('graphql.processor.class'))
        ->public()
        ->arg(0, service('graphql.execution_context'))
        ->arg(1, service('event_dispatcher'))
        ->call('setSecurityManager', [service('graphql.security_manager')]);

    $services
        ->set('graphql.security_manager', param('graphql.security_manager.class'))
        ->lazy()
        ->arg(0, service('security.authorization_checker'))
        ->arg(1, param('graphql.security.guard_config'));

    $services
        ->set('graphql.security.voter', BlacklistVoter::class)
        ->tag('security.voter', ['priority' => 255]);

    $services
        ->set('graphql.command.configure', GraphQLConfigureCommand::class)
        ->tag('console.command', ['command' => 'graphql:configure'])
        ->arg(0, service('service_container'));
};
