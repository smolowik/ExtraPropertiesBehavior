<?php
namespace smolowik\Propel\Behavior\ExtraProperties;

use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Generator\Model\Behavior;

/**
 * This file declare the ExtraPropertiesBehaviorQueryBuilderModifier class.
 *
 * @copyright (c) Carpe Hora SARL 2011
 * @since 2011-11-25
 * @license     MIT License
 */

/**
 * @author Julien Muetton <julien_muetton@carpe-hora.com>
 * @package propel.generator.behavior.extra_properties
 */
class ExtraPropertiesBehaviorQueryBuilderModifier
{
    protected $behavior, $table, $builder, $objectClassname, $peerClassname, $queryClassname, $pluralizer;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getPluralForm($root)
    {
        if ($this->pluralizer === null) {
            $this->pluralizer = new StandardEnglishPluralizer();
        }

        return $this->pluralizer->getPluralForm($root);
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassname = $builder->getStubObjectBuilder()->getClassname();
        $this->queryClassname = $builder->getStubQueryBuilder()->getClassname();
        $this->peerClassname = $this->queryClassname;
    }

    protected function getPropertyTableName()
    {
        $propertyTable = $this->behavior->getPropertyTable();
        $propertyARClassname = $this->builder->getNewStubObjectBuilder($propertyTable)->getClassname();
        return $propertyARClassname;
    }

    public function queryMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';

        $script .= $this->addFilterByExtraProperty($builder);
        $script .= $this->addFilterByExtraPropertyWithDefault($builder);

        return $script;
    }


    protected function addFilterByExtraProperty($builder)
    {
        return $this->behavior->renderTemplate('queryFilterByExtraProperty', array(
            'propertyName'                  => $this->getParameter('property_name'),
            'propertyNameMethod'            => ucfirst($this->getParameter('property_name')),
            'peerClassName'                 => $this->peerClassname,
            'shouldNormalize'               => 'true' === $this->getParameter('normalize'),
            'queryClassName'                => $this->queryClassname,
            'joinExtraPropertyTableMethod'  => $this->getJoinExtraPropertyTableMethodName(),
            'propertyPropertyNameColName'   => $this->getPropertyColumnPhpName('property_name_column'),
            'propertyPropertyValueColName'  => $this->getPropertyColumnPhpName('property_value_column'),
        ));
    }

    protected function addFilterByExtraPropertyWithDefault($builder)
    {
        return $this->behavior->renderTemplate('queryFilterByExtraPropertyWithDefault', array(
            'propertyName'                  => $this->getParameter('property_name'),
            'propertyNameMethod'            => ucfirst($this->getParameter('property_name')),
            'peerClassName'                 => $this->peerClassname,
            'shouldNormalize'               => 'true' === $this->getParameter('normalize'),
            'queryClassName'                => $this->queryClassname,
            'joinExtraPropertyTableMethod'  => $this->getJoinExtraPropertyTableMethodName(),
            'propertyPropertyNameColName'   => $this->getPropertyColumnPhpName('property_name_column'),
            'propertyPropertyValueColName'  => $this->getPropertyColumnPhpName('property_value_column'),
        ));
    }


    protected function getJoinExtraPropertyTableMethodName()
    {
        return 'leftJoin' . $this->getPropertyTableName();
    }


    protected function getPropertyColumnPhpName($name = 'property_name_column')
    {
        return $this->behavior->getPropertyColumnForParameter($name)->getPhpName();
    }

    public function staticMethods()
    {
        $propertyName = $this->getParameter('property_name');
        $propertyNameMethod = ucfirst($propertyName);
        $propertiesName = $this->getPluralForm($propertyName);
        $propertiesNameMethod = ucfirst($propertiesName);

        $script = <<<EOF
/**
 * Normalizes {$propertyName} name.
 *
 * @param String \${$propertyName}Name the {$propertyName} name to normalize.
 * @param String the normalized {$propertyName} name
 */
static function normalize{$propertyNameMethod}Name(\${$propertyName}Name)
{

EOF;
        if ($this->shouldNormalize()) {
            $script .= <<<EOF
  return strtoupper(\${$propertyName}Name);
EOF;
        } else {
            $script .= <<<EOF
  return \${$propertyName}Name;
EOF;
        }
        $script .= <<<EOF

}

/**
 * Normalizes {$propertyName} name.
 *
 * @deprecated see normalize{$propertyNameMethod}Name()
 *
 * @param String \${$propertyName}Name the {$propertyName} name to normalize.
 * @param String the normalized {$propertyName} name
 */
static function normalizeExtraPropertyName(\${$propertyName}Name)
{
  return self::normalize{$propertyNameMethod}Name(\${$propertyName}Name);
}

/**
 * Normalizes {$propertyName} value.
 *
 * @param String \${$propertyName}Value the {$propertyName} value to normalize.
 * @param String the normalized {$propertyName} value
 */
static function normalize{$propertyNameMethod}Value(\${$propertyName}Value)
{
  return \${$propertyName}Value;
}

/**
 * Normalizes {$propertyName} value.
 *
 * @deprecated see normalize{$propertyNameMethod}Value()
 *
 * @param String \${$propertyName}Value the {$propertyName} value to normalize.
 * @param String the normalized {$propertyName} value
 */
static function normalizeExtraPropertyValue(\${$propertyName}Value)
{
  return self::normalize{$propertyNameMethod}Value(\${$propertyName}Value);
}
EOF;

        return $script;
    }


    public function shouldNormalize()
    {
        return 'true' === $this->getParameter('normalize');
    }

} // END OF ExtraPropertiesBehaviorQueryBuilderModifier
