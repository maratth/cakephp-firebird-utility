<?php
namespace CakephpFirebird\View;

/**
 *
 * @author Valentin REYDY <valentin@microtec.fr>
 */
trait FirebirdViewTrait {
    
    public function initializeFirebirdUI() {
        $this->loadHelper('Firebird', ['className' => 'CakephpFirebird.Firebird']);
    }
    
}
