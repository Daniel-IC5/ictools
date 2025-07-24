<?php

declare( strict_types = 1 );
error_reporting( E_ALL );

use IPS\Application;
use IPS\Db;
use IPS\Member;
use const IPS\ROOT_PATH;


$communityPath = getcwd();
if( !$argv[ 1 ]??FALSE )
{
	$communityPath = $argv[ 1 ];
}
require_once $communityPath.'/init.php';

$metadataPath = $communityPath.'/.phpstorm.meta.php';

if( !is_dir( $metadataPath ) )
{
	mkdir( $metadataPath );
}

if( !is_dir( $metadataPath.'/classes' ) )
{
	mkdir( $metadataPath.'/classes' );
}


$constants = '<?php';
foreach( \IPS\IPS::defaultConstants() as $c => $v )
{
	$type = gettype( $v );
	match ( $type )
	{
		'boolean' => $v = $v ? 'true' : 'false',
		'integer' => $v = (string) $v,
		'double' => $v = (string) $v,
		'string' => $v = "'{$v}'",
		'array' => $v = '[]',
		'object' => $v = '[]',
		'resource' => $v = '[]',
		'NULL' => $v = 'null',
		'unknown type' => $v = '[]',
	};

	$constants .= PHP_EOL."define( 'IPS\\$c', {$v} );".PHP_EOL;
}

file_put_contents( $metadataPath.'/constants.php', $constants );


/* IPS Internal Constants */
if( file_exists( __DIR__.'/ipsInternalConstants.php' ) )
{
	$newConstants = '';
	$additionalConstants = [];
	require_once __DIR__.'/ipsInternalConstants.php';
	foreach( $additionalConstants as $c => $v )
	{
		$type = gettype( $v );
		match ( $type )
		{
			'boolean' => $v = $v ? 'true' : 'false',
			'integer' => $v = (string) $v,
			'double' => $v = (string) $v,
			'string' => $v = "'{$v}'",
			'array' => $v = '[]',
			'object' => $v = '[]',
			'resource' => $v = '[]',
			'NULL' => $v = 'null',
			'unknown type' => $v = '[]',
		};

		$newConstants .= PHP_EOL."define( '{$c}', {$v} );".PHP_EOL;
	}
	file_put_contents( $metadataPath.'/ipsconstants.php', $newConstants );
}


$output = <<<"META"
<?php
namespace PHPSTORM_META {

	exitPoint(\IPS\Output::error());
	exitPoint(\IPS\Output::sendOutput());
	exitPoint(\IPS\Output::json());
	exitPoint(\IPS\Output::redirect());
	exitPoint(\IPS\Output::showOffline());
	exitPoint(\IPS\Output::showBanned());
}
META;

file_put_contents( $metadataPath.'/output.php', $output );


$tables = \IPS\Db::i()->getTables( \IPS\Db::i()->prefix );
$m = <<<"META"
<?php
namespace PHPSTORM_META {

registerArgumentsSet('tables',
META;

foreach( $tables as $tableName )
{
	$m .= "'$tableName',".PHP_EOL;
}

$m .= ");
 	expectedArguments(\IPS\Db::select(), 1, argumentsSet('tables'));
	expectedArguments(\IPS\Db::delete(), 0, argumentsSet('tables'));
	expectedArguments(\IPS\Db::insert(), 0, argumentsSet('tables'));
	expectedArguments(\IPS\Db::update(), 0, argumentsSet('tables'));
}";
file_put_contents( $metadataPath.'/tables.php', $m );


$words = [];
$lang = [];
foreach( \IPS\Application::applications() as $app )
{
	if( file_exists( \IPS\ROOT_PATH."/applications/{$app->directory}/dev/lang.php" ) )
	{
		require \IPS\ROOT_PATH."/applications/{$app->directory}/dev/lang.php";
		$words = array_merge( $words, $lang );
	}
}

$l = <<<"META"
<?php
namespace PHPSTORM_META {

registerArgumentsSet('languages',
META;

foreach( $words as $tableName => $word )
{
	$l .= "'$tableName',".PHP_EOL;
}

$l .= ");
 	expectedArguments(\IPS\Lang::addToStack(), 0, argumentsSet('languages'));
 	expectedArguments(\IPS\Lang::get(), 0, argumentsSet('languages'));
 	expectedArguments(\IPS\Output::error(), 0, argumentsSet('languages'));
}";
file_put_contents( $metadataPath.'/lang.php', $l );


$settings = \IPS\Db::i()->select( 'conf_key', 'core_sys_conf_settings' );

$file = <<<FILE
<?php
namespace IPS;

class  Settings{
     
FILE;
foreach( $settings as $setting )
{
	$file .= 'public string $'.$setting.';'.PHP_EOL;
}

$file .= '}';
file_put_contents( $metadataPath.'/settings_data.php', $file );


$l = <<<"META"
<?php
namespace PHPSTORM_META {

registerArgumentsSet('applications',
META;

foreach( \IPS\Application::applications() as $app )
{
	$l .= "'$app->directory',".PHP_EOL;
}

$l .= ");
 	expectedArguments(\IPS\Application::load(), 0, argumentsSet('applications'));
 	expectedArguments(\IPS\Application::extensions(), 0, argumentsSet('applications'));
 	expectedArguments(\IPS\\Email::buildFromTemplate() , 0, argumentsSet('applications'));
 	expectedArguments(\IPS\Application::allExtensions(), 0, argumentsSet('applications'));
   	expectedArguments(\IPS\Application::appIsEnabled(), 0, argumentsSet('applications'));
 
}";
file_put_contents( $metadataPath.'/applications.php', $l );


function generateGettersAndSetters( $class )
{
	global $metadataPath;

	$reflection = new ReflectionClass( $class );

	$methods = $reflection->getMethods( ReflectionMethod::IS_PUBLIC );

	$getters = [];
	$setters = [];

	foreach( $methods as $method )
	{
		if( str_starts_with( $method->name, 'get_' ) )
		{
			$n = str_replace( 'get_', '', $method->name );
			$getters[ $n ] = $method->getReturnType();
		}elseif( str_starts_with( $method->name, 'set_' ) )
		{
			$n = str_replace( 'set_', '', $method->name );
			$setters[ $n ] = $method->getReturnType();
		}
	}

	$columns = [];
	if( isset( $class::$databaseTable ) )
	{
		$db = $class::$databaseTable;
		$schema = Db::i()->getTableDefinition( $db );
		$columns = [];
		foreach( $schema[ 'columns' ] as $column )
		{
			//DO type mapping .. $schema['columns']['type']
			$name = str_replace( $class::$databasePrefix, '', $column[ 'name' ] );
			if( !isset( $getters[ $name ] ) and !isset( $setters[ $name ] ) )
			{
				$columns[] = $name;
			}

		}
	}


	$text = '';
	$start = <<<REG
<?php
namespace {$reflection->getNamespaceName()};
/**

REG;

	foreach( $getters as $getterName => $type )
	{
		$text .= '* @property-read '.$type.' '.$getterName.PHP_EOL;
	}
	foreach( $setters as $getterName => $type )
	{
		$text .= '* @property-write '.$type.' '.$getterName.PHP_EOL;
	}

	foreach( $columns as $column )
	{
		$text .= '* @property '.$type.' '.$column.PHP_EOL;
	}

	$start .= $text;
	$start .= ' '.PHP_EOL;
	$start .= ' */'.PHP_EOL;
	$start .= "class {$reflection->getShortName()}{".PHP_EOL;
	$start .= '}';

	$filename = str_replace( '\\', '', $reflection->getName() );

	file_put_contents( $metadataPath.'/classes'.'/'.$filename.'.php', $start );
}

$vars = [
'module'     => 'string',
'controller' => 'string',
'do'         => 'string',
];
generateClass( 'IPS', 'Request', $vars );

function generateClass( string $ns, string $classname, array $vars )
{
	global $metadataPath;
	$file = <<<FILE
<?php
namespace {$ns};

/**


FILE;

	foreach( $vars as $name => $type )
	{
		$file .= '* @property '.$type.' '.$name.PHP_EOL;
	}
	$file .= ' '.PHP_EOL;
	$file .= '*/'.PHP_EOL;
	$file .= "class {$classname}{".PHP_EOL;
	$file .= '}';
	$filename = str_replace( '\\', '', $ns."\\".$classname );

	file_put_contents( $metadataPath.'/classes'.'/'.$filename.'.php', $file );
}

$l = <<<"META"
<?php
namespace PHPSTORM_META {

registerArgumentsSet('extensions',
META;
foreach( Application::applications() as $app )
{
	if( is_dir( ROOT_PATH."/applications/{$app->directory}/data/defaults/extensions" ) )
	{
		foreach( new DirectoryIterator( ROOT_PATH."/applications/{$app->directory}/data/defaults/extensions" ) as $file )
		{
			if( mb_substr( $file->getFilename(), 0, 1 ) !== '.' and $file->getFilename() != 'index.html' )
			{
				$l .= "'".str_replace( '.txt', '', $file->getFilename() )."',".PHP_EOL;
			}
		}
	}
}
$l .= ");
 	expectedArguments(\IPS\Application::allExtensions, 1, argumentsSet('extensions'));
}";
file_put_contents( $metadataPath.'/extensions.php', $l );


//TODO build a proper iterator for this, iterate over all apps and system folder...
generateGettersAndSetters( Member::class );
generateGettersAndSetters( Member\Group::class );
generateGettersAndSetters( Member\Club::class );
generateGettersAndSetters( Application::class );

echo 'done';
