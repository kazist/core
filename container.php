<?php

use Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Kazist\Service\Security\Encoder\HashedMD5Encoder;
use DebugBar\StandardDebugBar;

$sc = new DependencyInjection\ContainerBuilder();

if (file_exists(__DIR__ . '/config.php')) {
    include __DIR__ . '/config.php';
}

if (file_exists(__DIR__ . '/../setting/config.php')) {
    include __DIR__ . '/../setting/config.php';
}

$session = new Session();
$session->start();

if ($sc->hasParameter('system.debugging') && $sc->getParameter('system.debugging')) {
    $debugbar = new StandardDebugBar();
}

if (!(SYSTEM_INSTALL === true)) {
    $sc->register('doctrine', 'Kazist\Service\Database\Doctrine');
}

$sc->register('session', $session);


/* -------------------------- General settings ------------------- */

$sc->setParameter('charset', 'UTF-8');
$sc->setParameter('routes', include __DIR__ . '/app.php');

if ($sc->hasParameter('system.debugging') && $sc->getParameter('system.debugging')) {
    $sc->register('debugger', $debugbar);
}

$sc->register('twig', 'Kazist\Service\Renderer\Twig');
$sc->register('context', 'Symfony\Component\Routing\RequestContext');
$sc->register('matcher', 'Kazist\Service\NewUrlMatcher')
        ->setArguments(array('%routes%', new Reference('context')))
;
$sc->register('request_stack', 'Symfony\Component\HttpFoundation\RequestStack');
$sc->register('resolver', 'Kazist\Service\ContainerAwareControllerResolver')
        ->setArguments(array(null, $sc));

/* --------------------------Assets Objects ------------------- */

$sc->setParameter('asset.merge', false);
$sc->setParameter('asset.minify', true);


/* --------------------------Security Objects ------------------- */
$sc->setParameter('security.strategy', AccessDecisionManager::STRATEGY_AFFIRMATIVE);
$sc->setParameter('security.encode_list', array(
    'Kazist\Service\Security\User' => new PlaintextPasswordEncoder(),
    'Kazist\Service\Security\User' => new MessageDigestPasswordEncoder('md5', false, 1),
    'Kazist\Service\Security\User' => new HashedMD5Encoder(),
));

$sc->register('security.token_storage', 'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage');
$sc->register('security.user_checker', 'Kazist\Service\Security\NewUserChecker');
$sc->register('security.user_provider', 'Kazist\Service\Security\UserProvider');
$sc->register('security.encoder_factory', 'Symfony\Component\Security\Core\Encoder\EncoderFactory')
        ->setArguments(array('%security.encode_list%'));

//Password provider
$sc->register('security.dao_auth_1', 'Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider')
        ->setArguments(array(new Reference('security.user_provider'), new Reference('security.user_checker'), 'main', new Reference('security.encoder_factory'), true));
$sc->setParameter('security.provider_list', array(
    new Reference('security.dao_auth_1'),
));
$sc->register('security.authenticate', 'Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager')
        ->setArguments(array('%security.provider_list%', true));

//Role Voters
$sc->register('security.voter_1', 'Symfony\Component\Security\Core\Authorization\Voter\RoleVoter')
        ->setArguments(array('ROLE_'));
$sc->register('security.voter_2', 'Kazist\Service\Security\Voter\PermissionVoter');
$sc->setParameter('security.voters', array(
    new Reference('security.voter_1'),
    new Reference('security.voter_2'),
));
$sc->register('security.access_decision', 'Symfony\Component\Security\Core\Authorization\AccessDecisionManager')
        ->setArguments(array('%security.voters%', '%security.strategy%', false, true));

//Tie authorization & authentication & token storage together 
$sc->register('security.user_check', 'Symfony\Component\Security\Core\Authorization\AuthorizationChecker')
        ->setArguments(array(new Reference('security.token_storage'), new Reference('security.authenticate'), new Reference('security.access_decision'), false));

/* --------------------------Listeners Objects ------------------- */

$sc->register('listener.router', 'Symfony\Component\HttpKernel\EventListener\RouterListener')
        ->setArguments(array(new Reference('matcher'), new Reference('request_stack')))
;
$sc->register('listener.response', 'Symfony\Component\HttpKernel\EventListener\ResponseListener')
        ->setArguments(array('%charset%'))
;
$sc->register('listener.exception', 'Symfony\Component\HttpKernel\EventListener\ExceptionListener')
        ->setArguments(array('Kazist\\Controller\\ErrorController::exceptionAction'))
;


$sc->register('dispatcher', 'Symfony\Component\EventDispatcher\EventDispatcher')
        ->addMethodCall('addSubscriber', array(new Reference('listener.router')))
        ->addMethodCall('addSubscriber', array(new Reference('listener.response')))
        ->addMethodCall('addSubscriber', array(new Reference('listener.exception')))
;
if (!(SYSTEM_INSTALL === true)) {
    include 'listener.php';
}

$sc->register('framework', 'Kazist\Framework')
        ->setArguments(array(new Reference('dispatcher'), new Reference('resolver')))
;

$sc->setParameter('debug', true);


// Fetch from Folder
$files = scandir(JPATH_ROOT . 'include/Kazist/Listener');

foreach ($files as $key => $file_name) {
    
    if (strpos($file_name, '.php')) {

        $name = str_replace('.php', '', $file_name);
        $listener_name = 'listener.' . strtolower($name);
        $class_name = 'Kazist\\Listener\\' . $name;

        if ($name !== 'KazistKernelListener') {

            $sc->register($listener_name, $class_name);
            $sc->getDefinition('dispatcher')
                    ->addMethodCall('addSubscriber', array(new Reference($listener_name)))
            ;
        }
    }
}

return $sc;
