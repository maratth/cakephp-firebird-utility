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
     * La clef sequence est ajouter dans le hook d'initialisation.
     * 
     * @var array
     */
    protected $_defaultConfig = [
        'field' => 'code',
        'offset' => '1',
        'base' => false
    ];
    
    /**
     * Initilize le behavior.
     * @param array $config
     * @throws CakephpFirebirdException
     */
    public function initialize(array $config) {
        $config += $this->config();
        
        if (!isset($config['sequence'])) {
            $config['sequence'] = 'gen_' . $this->_table->table();
        }
        
        $this->config($config);
    }
    
    /**
     * Execute genId pour générer un id.
     * Si le code n'est pas définie.
     * @param Event $event
     * @param EntityInterface $entity
     */
    public function beforeSave(Event $event, EntityInterface $entity) {
        $config = $this->config();
        
        if ($entity->get($config['field']) == null) {
            $stmt = $this->_table->connection()->query(
                    sprintf(
                            'SELECT GEN_ID(%s, %s)%s FROM RDB$DATABASE', 
                            $config['sequence'], 
                            $config['offset'], 
                            $config['base'] ? "+ {$config['base']}" : ''
                    )
            );

            $entity->set($config['field'], $stmt->fetch()[0]);
        }
    }

}
