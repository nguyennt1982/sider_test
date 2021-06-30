<?php
namespace App\Controller;

class BController extends AppController
{
    /**
     * Components
     *
     * @var array
     */
    public $components = ['Paginator', 'Flash'];

    /** @var array Used Model */
    public $uses = ['AbTest'];

    /** @var string UseLayout */
    public $layout = 'admin';

    /**
     * {@inheritdoc}
     */
    public function beforeFilter()
    {
        alo
    }

    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
    }

    /**
     * add method
     */
    public function add()
    {
    }

    /**
     * edit method
     *
     * @param int $id AbTestId
     */
    public function edit($id = null)
    {
    }

    /**
     * delete method
     *
     * @param int $id AbTestId
     * @return \Cake\Network\Response|null
     */
    public function delete($id = null)
    {
    }
}
