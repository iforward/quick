<?php
namespace q\base;

class InvalidRouteException extends Exception {
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName() {
        return 'Invalid Route';
    }
}

?>
