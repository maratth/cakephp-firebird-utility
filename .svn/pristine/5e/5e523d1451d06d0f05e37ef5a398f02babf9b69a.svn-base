<?php
namespace CakephpFirebird\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * Boolean behavior.
 * 
 * Convertie les boolean Microtec 'Oui' et 'Non' en vraie boolean.
 */
class BooleanBehavior extends Behavior
{

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];
    
    /**
     * Evenement beforeFind, Injecte une fonction pour convertir les boolean.
     * 'Oui' => true, 'Non' => false.
     * Vérifie tout les champs présent dans la requete et si un champ
     *  est un string et qu'il contient 'Oui' ou 'Non' alors cette valeur 
     *  est convertie en boolean.
     * 
     * @param Event $event
     * @param type $query
     * @param type $options
     */
    public function beforeFind(Event $event, Query $query, ArrayObject $options) {
        $query->formatResults(function($results) {
            return $results->map(function($row) {
                if (is_null($row)) {
                    return $row;
                }
                $tab = is_array($row) ? $row : $row->toArray(); // Au cas ou les données ne sont pas hydraté.
                
                foreach ($tab as $key => $value) {
                    if (is_string($value) && in_array(trim($value), ['Oui', 'Non'], true)) {
                        $row[$key] = (trim($row[$key]) === 'Oui');
                    }
                }
                
                return $row;
            });
        });
    }
    
    /**
     * Envenement beforeSave, Récupère les données modifiées
     *  puis si c'est un boolean, convertie sa valeur en boolean Microtec.
     * 
     * @param Event $event Evenement Cake
     * @param EntityInterface $entity Entite concernée
     * @param ArrayObject $options Options de la Query
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options) {
        if ($entity->dirty()) {
            
            if ($entity instanceof Entity && !empty($entity->source())) {
                $repository = \Cake\ORM\TableRegistry::get($entity->source());
                $datas = $entity->extract($repository->schema()->columns(), true);
                
                foreach ($datas as $propertyName => $data) {
                    if (is_bool($data)) {
                        $entity->set($propertyName, $data ? 'Oui' : 'Non');
                    }
                }
            }
        }
    }

}
