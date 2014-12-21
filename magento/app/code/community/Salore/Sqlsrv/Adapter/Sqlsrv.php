<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade SolrBridge to newer
 * versions in the future.
 *
 * @category    Salore
 * @package     Salore_Sqlsrv
 * @author      Salore team
 * @copyright   Copyright (c) Salore team
 */
class Salore_Sqlsrv_Adapter_Sqlsrv extends Salore_Sqlsrv_Adapter_Abstract {
    /**
     * Insert data to table in Database
     * 
     * @param string $table
     *            , array $bind
     * @return statement resources
     */
    public function insert($table, array $bind) {
        $connection = $this->getConnection ();
        if ($connection) {
            /* Begin the transaction. */
            if (sqlsrv_begin_transaction ( $connection ) === false) {
                print_r ( sqlsrv_errors (), true );
            }
            
            /* Initialize parameter values and sql. */
            $cols = array ();
            $vals = array ();
            $param = array ();
            foreach ( $bind as $col => $val ) {
                $param [] = $val;
                $cols [] = $col;
                if ($val instanceof Zend_Db_Expr) {
                    $vals [] = $val->__toString ();
                    unset ( $bind [$col] );
                } else {
                    $vals [] = '?';
                }
            }
            // build the statement
            $sql = "INSERT INTO " . $table . ' (' . implode ( ', ', $cols ) . ') ' . 'VALUES (' . implode ( ', ', $vals ) . ')';
            // execute the statement
            $stmt = sqlsrv_query ( $connection, $sql, $param );
            if ($stmt === false) {
                print_r ( sqlsrv_errors (), true );
                sqlsrv_rollback ( $connection );
            } else {
                sqlsrv_commit ( $connection );
                sqlsrv_free_stmt ( $stmt );
            }
        } else {
            print_r ( sqlsrv_errors (), true );
        }
    }
    /**
     * Update table in Database
     * 
     * @param string $table
     *            , string $where
     * @return statement resources
     */
    public function update($table, array $bind, $where = '') {
        /**
         * Build "col = ?" pairs for the statement,
         * except for Zend_Db_Expr which is treated literally.
         */
        $connection = $this->getConnection ();
        if ($connection) {
            /* Begin the transaction. */
            if (sqlsrv_begin_transaction ( $connection ) === false) {
                print_r ( sqlsrv_errors (), true );
            }
            $set = array ();
            $param = array ();
            $i = 0;
            foreach ( $bind as $col => $val ) {
                $param [] = $val;
                if ($val instanceof Zend_Db_Expr) {
                    $val = $val->__toString ();
                    unset ( $bind [$col] );
                } else {
                    if ($this->supportsParameters ( 'positional' )) {
                        $val = '?';
                    } else {
                        if ($this->supportsParameters ( 'named' )) {
                            unset ( $bind [$col] );
                            $bind [':col' . $i] = $val;
                            $val = ':col' . $i;
                            $i ++;
                        } else {
                            /**
                             * @see Zend_Db_Adapter_Exception
                             */
                            throw new Zend_Db_Adapter_Exception ( get_class ( $this ) . " doesn't support positional or named binding" );
                        }
                    }
                }
                $set [] = $col . ' = ' . $val;
            }
            
            $where = $this->_whereExpr ( $where );
            
            /**
             * Build the UPDATE statement
             */
            $sql = "UPDATE " . $table . ' SET ' . implode ( ', ', $set ) . (($where) ? " WHERE $where" : '');
            /**
             * Execute the statement and return the number of affected rows
             */
            $stmt = sqlsrv_query ( $connection, $sql, $param );
            if ($stmt === false) {
                print_r ( sqlsrv_errors (), true );
                sqlsrv_rollback ( $connection );
                echo "Transaction rolled back.<br />";
            } else {
                sqlsrv_commit ( $connection );
                sqlsrv_free_stmt ( $stmt );
            }
        } else {
            print_r ( sqlsrv_errors (), true );
        }
    }
    
    /**
     * Delete table in Database
     * 
     * @param string $table
     *            , string $where
     * @return statement resources
     */
    public function delete($table, $where = '') {
        $connection = $this->getConnection ();
        if ($connection) {
            /* Begin the transaction. */
            if (sqlsrv_begin_transaction ( $connection ) === false) {
                print_r ( sqlsrv_errors (), true );
            }
            $where = $this->_whereExpr ( $where );
            
            /**
             * Build the DELETE statement
             */
            $sql = "DELETE FROM " . $table . (($where) ? " WHERE $where" : '');
            /**
             * Execute the statement and return the number of affected rows
             */
            $stmt = sqlsrv_query ( $connection, $sql );
            if ($stmt === false) {
                print_r ( sqlsrv_errors (), true );
                sqlsrv_rollback ( $connection );
            } else {
                sqlsrv_commit ( $connection );
            }
        } else {
            print_r ( sqlsrv_errors (), true );
        }
    }
    /**
     * Creates and returns a new Zend_Db_Select object for this adapter.
     *
     * @return Varien_Db_Select
     */
    public function select() {
        return new Varien_Db_Select ( $this );
    }
}