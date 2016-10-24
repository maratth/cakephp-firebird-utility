<?php

namespace CakephpFirebird\Model\Behavior;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;

/**
 * Sequence behavior.
 * 
 * Ce Behavior permet d'utiliser une sequence pour générer un id.
 */
class SequenceBehavior extends Behavior {

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'field' => 'code'
    ];
    
    /**
     * Initilize le behavior.
     * @param array $config
     * @throws CakephpFirebirdException
     */
    public function initialize(array $config) {
        $config = $this->config();
        
        if (!isset($config['sequence'])) {
            $config['sequence'] = 'gen_' . $this->_table->table();
        }
        
        $this->config($config);
    }

    /**
     * Execute une requete sur la base pour générer un code.
     * @param Entity $entity
     */
    public function genId(Entity $entity) {
        $config = $this->config();
        
        $stmt = $this->_table->connection()->query(
                sprintf('SELECT GEN_ID(%s, 1) FROM RDB$DATABASE', $config['sequence'])
        );
        
        $entity->set($config['field'], $stmt->fetch()[0]);
    }
    
    /**
     * Execute genId pour générer un id.
     * @param Event $event
     * @param EntityInterface $entity
     */
    public function beforeSave(Event $event, EntityInterface $entity) {
        $this->genId($entity);
    }

}
