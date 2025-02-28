<?php
/**
 * SF_Model_Abstract
 * 
 * Base model class that all our models will inherit from.
 * 
 * The main purpose of the base model is to provide models with a way
 * of handling their resources.
 * 
 * @category   Storefront
 * @package    SF_Model
 * @copyright  Copyright (c) 2008 Keith Pope (http://www.thepopeisdead.com)
 * @license    http://www.thepopeisdead.com/license.txt     New BSD License
 */
abstract class SF_Model_Abstract implements SF_Model_Interface 
{
    /**
    * @var array Class methods
    */
    protected $_classMethods;
    
    /**
     * @var array Model resource instances
     */
    protected $_resources = array();

    /**
     * @var array Form instances
     */
    protected $_forms = array();

    /**
     * @var Zend_Acl
     */
    protected $_acl;

    /**
     * @var string
     */
    protected $_identity;
    
   /**
    * Constructor
    *
    * @param array|Zend_Config|null $options
    * @return void
    */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if (is_array($options)) {
            $this->setOptions($options);
        }

        $this->init();
    }

    /**
     * Constructor extensions
     */
    public function init()
    {}

   /**
    * Set options using setter methods
    *
    * @param array $options
    * @return SF_Model_Abstract 
    */
    public function setOptions(array $options)
    {
        if (null === $this->_classMethods) {
            $this->_classMethods = get_class_methods($this);
        }
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $this->_classMethods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

	/**
	 * Get a resource
	 *
	 * @param string $name
	 * @return SF_Model_Resource_Interface 
	 */
	public function getResource($name) 
	{
        if (!isset($this->_resources[$name])) {
            $class = join('_', array(
                    $this->_getNamespace(),
                    'Resource',
                    $this->_getInflected($name)
            ));
            $this->_resources[$name] = new $class();
        }
	    return $this->_resources[$name];
	}

    /**
     * Get a Form
     * 
     * @param string $name
     * @return Zend_Form 
     */
    public function getForm($name)
    {
        if (!isset($this->_forms[$name])) {
            $class = join('_', array(
                    $this->_getNamespace(),
                    'Form',
                    $this->_getInflected($name)
            ));
            $this->_forms[$name] = new $class(array('model' => $this));
        }
	    return $this->_forms[$name];
    }

    /**
     * Classes are named spaced using their module name
     * this returns that module name or the first class name segment.
     * 
     * @return string This class namespace 
     */
    private function _getNamespace()
    {
        $ns = explode('_', get_class($this));
        return $ns[0];
    }

    /**
     * Inflect the name using the inflector filter
     * 
     * Changes camelCaseWord to Camel_Case_Word
     * 
     * @param string $name The name to inflect
     * @return string The inflected string 
     */
    private function _getInflected($name)
    {
        $inflector = new Zend_Filter_Inflector(':class');
        $inflector->setRules(array(
            ':class'  => array('Word_CamelCaseToUnderscore')
        ));
        return ucfirst($inflector->filter(array('class' => $name)));
    }

    /**
     * Set the identity of the current request
     * 
     * @param array|string|null|Zend_Acl_Role_Interface $identity
     * @return SF_Model_Abstract
     */
    public function setIdentity($identity)
    {
        if (is_array($identity)) {
            if (!isset($identity['role'])) {
                $identity['role'] = 'Guest';
            }
            $identity = new Zend_Acl_Role($identity['role']);
        } elseif (is_scalar($identity) && !is_bool($identity)) {
            $identity = new Zend_Acl_Role($identity);
        } elseif (null === $identity) {
            $identity = new Zend_Acl_Role('Guest');
        } elseif (!$identity instanceof Zend_Acl_Role_Interface) {
            throw new Spindle_Model_Exception('Invalid identity provided');
        }
        $this->_identity = $identity;
        return $this;
    }

    /**
     * Get the identity, if no ident use guest
     * 
     * @return string
     */
    public function getIdentity()
    {
        if (null === $this->_identity) {
            $auth = Zend_Auth::getInstance();
            if (!$auth->hasIdentity()) {
                return 'Guest';
            }
            $this->setIdentity($auth->getIdentity());
        }
        return $this->_identity;
    }

    /**
     * Check the acl
     * 
     * @param string $action
     * @return boolean 
     */
    public function checkAcl($action)
    {
        return $this->getAcl()->isAllowed(
            $this->getIdentity(),
            $this,
            $action
        );
    }
}