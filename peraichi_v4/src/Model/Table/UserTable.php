<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Class UserTable
 *
 * @package App\Model\Table
 */
class UserTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     *
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setAlias('User');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->initRelationship();
    }

    /**
     * initRelationship method
     */
    protected function initRelationship()
    {
        $this->belongsTo('Facebooks', [
            'foreignKey' => 'facebook_id',
        ]);
        $this->belongsTo('Googles', [
            'foreignKey' => 'google_id',
        ]);
        $this->belongsTo('Raksuls', [
            'foreignKey' => 'raksul_id',
        ]);
        $this->belongsTo('Groups', [
            'foreignKey' => 'group_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     *
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('id')
            ->maxLength('id', 36)
            ->allowEmptyString('id', null, 'create');

        $validator->integer('page_count_limit')
            ->allowEmptyString('page_count_limit');

        $validator->scalar('username')
            ->maxLength('username', 191)
            ->requirePresence('username', 'create')
            ->notEmptyString('username');

        $validator->scalar('slug')
            ->maxLength('slug', 191)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug');

        $validator->scalar('password')
            ->maxLength('password', 128)
            ->allowEmptyString('password');

        $validator->scalar('password_token')
            ->maxLength('password_token', 128)
            ->allowEmptyString('password_token');

        $validator->email('email')
            ->allowEmptyString('email');

        $validator->boolean('email_verified')
            ->allowEmptyString('email_verified');

        $validator->integer('email_verified_status')
            ->notEmptyString('email_verified_status');

        $validator->scalar('email_token')
            ->maxLength('email_token', 255)
            ->allowEmptyString('email_token');

        $validator->dateTime('email_token_expires')
            ->allowEmptyDateTime('email_token_expires');

        $validator->boolean('is_email_subscriber')
            ->allowEmptyString('is_email_subscriber');

        $validator->boolean('tos')
            ->allowEmptyString('tos');

        $validator->boolean('active')
            ->allowEmptyString('active');

        $validator->boolean('is_provisional')
            ->notEmptyString('is_provisional');

        $validator->boolean('ban')
            ->notEmptyString('ban');

        $validator->dateTime('last_login')
            ->allowEmptyDateTime('last_login');

        $validator->dateTime('last_action')
            ->allowEmptyDateTime('last_action');

        $validator->boolean('is_admin')
            ->allowEmptyString('is_admin');

        $validator->boolean('is_read_pakutaso_terms')
            ->allowEmptyString('is_read_pakutaso_terms');

        $validator->scalar('role')
            ->maxLength('role', 255)
            ->allowEmptyString('role');

        $validator->scalar('agency_subcode')
            ->maxLength('agency_subcode', 255)
            ->allowEmptyString('agency_subcode');

        $validator->scalar('channel_subcode')
            ->maxLength('channel_subcode', 255)
            ->allowEmptyString('channel_subcode');

        $validator->scalar('referral_code')
            ->maxLength('referral_code', 255)
            ->allowEmptyString('referral_code');

        $validator->scalar('referrer')
            ->maxLength('referrer', 255)
            ->allowEmptyString('referrer');

        $validator->scalar('shorten_url')
            ->maxLength('shorten_url', 255)
            ->allowEmptyString('shorten_url');

        $validator->boolean('is_applying_quant_listing')
            ->notEmptyString('is_applying_quant_listing');

        $validator->integer('team_spot_management_remain')
            ->notEmptyString('team_spot_management_remain');

        $validator->integer('team_member_limit')
            ->notEmptyString('team_member_limit');

        $validator->integer('across_account_page_copy_limit')
            ->notEmptyString('across_account_page_copy_limit');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     *
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['username']), ['errorField' => 'username']);
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);
        $rules->add($rules->existsIn(['facebook_id'], 'Facebooks'), ['errorField' => 'facebook_id']);
        $rules->add($rules->existsIn(['google_id'], 'Googles'), ['errorField' => 'google_id']);
        $rules->add($rules->existsIn(['raksul_id'], 'Raksuls'), ['errorField' => 'raksul_id']);
        $rules->add($rules->existsIn(['group_id'], 'Groups'), ['errorField' => 'group_id']);
        $rules->add($rules->existsIn(['enterprise_id'], 'Enterprises'), ['errorField' => 'enterprise_id']);
        $rules->add($rules->existsIn(['agency_id'], 'Agencies'), ['errorField' => 'agency_id']);
        $rules->add($rules->existsIn(['agency_user_id'], 'AgencyUsers'), ['errorField' => 'agency_user_id']);
        $rules->add($rules->existsIn(['channel_id'], 'Channels'), ['errorField' => 'channel_id']);
        $rules->add($rules->existsIn(['webpay_customer_id'], 'WebpayCustomers'), ['errorField' => 'webpay_customer_id']);
        $rules->add($rules->existsIn(['pg_member_id'], 'PgMembers'), ['errorField' => 'pg_member_id']);

        return $rules;
    }
}
