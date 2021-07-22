<?php

/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 */

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package app.Controller
 * @link    http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    /**
     * セッションキーリスト
     */
    public static $sessionKeys
        = [
            // クレジットカード登録モーダルの表示を一時的に無効にする
            'DISABLED_REGISTER_CREDIT_CARD_MODAL' => 'disabledRegisterCreditCardModal',
            // クレジットカード登録モーダルを表示する
            'OPEN_REGISTER_CREDIT_CARD_MODAL' => 'openRegisterCreditCardModal',
            // お試し系プラン継続モーダルの表示を一時的に無効にする
            'DISABLED_TRIAL_CONTRACT_MODAL' => 'disabledTrialContractModal',
            // お試し系プラン継続モーダルを表示する
            'OPEN_TRIAL_CONTRACT_MODAL' => 'openTrialContractModal',
        ];
    public $pageTitle = DEFAULT_PAGE_TITLE;
    public $pageMeta = ['name' => [], 'property' => []];
    private $processStartTimePoints;
    private $processId;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->initComponents();
    }

    /**
     * initComponents method
     *
     * @throws \Exception
     */
    protected function initComponents()
    {
        $this->loadComponent('RequestHandler', [
            'enableBeforeRedirect' => false,
        ]);
        $this->loadComponent('Flash');

        $this->loadComponent('UserAnalysisData');

        $this->loadComponent('Auth', [
            'loginAction' => [
                'controller' => 'Users',
                'action' => 'login',
            ],
            'authError' => 'ログイン期限が切れました。再度ログインをお願いします。',
            'authenticate' => [
                'Form' => [
                    'fields' => ['username' => 'email'],
                ],
            ],
            'storage' => 'Session',
        ]);
    }

    /**
     * beforeRedirect method
     *
     * @param EventInterface $url
     * @param null $status
     * @param bool $exit
     *
     * @return array|bool|\Cake\Http\Response|void|null
     */
    public function beforeRedirect($url, $status = null, $exit = true)
    {
        // 2020.12.15 GoogleChromeにてプロトコル(https)を明示的に指定しないリダイレクトはエラー画面が出てしまうため、rootURLから指定するように記載。
        $response = true;
        $protocol = Configure::read('environment.protocol');
        $publicHost = Configure::read('environment.public_host');
        $privateHost = Configure::read('environment.private_host');
        $isPeraichiHost = is_string($url) && (strpos($url, '://' . $publicHost) || strpos($url, '://' . $privateHost));
        $currentRootUrl = $protocol . '//' . $_SERVER["HTTP_HOST"];
        if (isset($url->controller) || isset($url->action)) {
            $response = ['url' => $currentRootUrl . Router::url($url)];
        } elseif (is_string($url) && strpos($url, $protocol) === false && $isPeraichiHost) {
            // リダイレクト先URLパスが環境プロトコルを含まないかつ独自ドメインでない場合
            $response = ['url' => $currentRootUrl . $url];
        } elseif (is_string($url) && (substr($url, 0, 1) === '/')) {
            // $this->redirect('/landing_pages')とかのパターン。
            $response = ['url' => $currentRootUrl . $url];
        }

        return $response;
    }

    /**
     * beforeRender method
     *
     * @param EventInterface $event
     *
     * @return \Cake\Http\Response|void|null
     */
    public function beforeRender(EventInterface $event)
    {
        if (Configure::read('record_process_time')) {
            $this->recordProcessTimeLog('at beforeRender');
        }
        $auth_user = $this->Auth->user();
        $this->set(compact('auth_user'));
        // メールアドレスの認証が完了しているかを確認
        $AppUser = TableRegistry::getTableLocator()->get('AppUser');
        $isVerifiedEmailUser = $AppUser->isVerifiedEmailUser($auth_user['id']);
        $needEmailVerify['needVerify'] = empty($isVerifiedEmailUser) && !empty($auth_user);
        $needEmailVerify['mailExpired'] = $auth_user['email_token_expires'] < date("Y-m-d");
        $this->set(compact('needEmailVerify'));

        // 決済オプション加入者のヘッダー表示用に未対応注文数取得
        $notDealtOrderCount = !empty($auth_user && $this->request->getSession()->read('funcs.' . FUNC_PAYMENT_PLAN)) ?
            TableRegistry::getTableLocator()->get('Order')->fetchCountNotDealtStatusOrders($auth_user['id']) : 0;

        $this->set(compact('notDealtOrderCount'));

        // Set meta params
        $this->set('title_for_layout', $this->pageTitle);
        $pageMetaList = Configure::read('page_meta', []);
        // request uri
        $uri = sprintf(
            '%s/%s',
            Inflector::camelize($this->request->getParam('controller')),
            Inflector::variable($this->request->getParam('action'))
        );

        // 除外uriのフィルター
        $ignoreUri = Hash::filter(Hash::get($pageMetaList, 'ignore'), function ($ignore) use ($uri) {
            return in_array($uri, [$ignore]);
        });

        $defaultMeta = Hash::get($pageMetaList, 'default');
        // ignoreされてない場合の設定(default + 個別設定)
        if (count($ignoreUri) <= 0) {
            $uriMeta = Hash::get($pageMetaList, "{$uri}", []);
            // 最終的なMeta情報を生成
            $this->pageMeta['name'] = array_merge(
                Hash::get(
                    $defaultMeta,
                    'name',
                    []
                ),
                Hash::get($uriMeta, 'name', [])
            );
            $this->pageMeta['property'] = array_merge(
                Hash::get(
                    $defaultMeta,
                    'property',
                    []
                ),
                Hash::get($uriMeta, 'property', [])
            );
        } else { // DBから取得するタイプ(default + DB設定)
            $this->pageMeta['name'] = array_merge(
                Hash::get(
                    $defaultMeta,
                    'name',
                    []
                ),
                $this->pageMeta['name']
            );
            $this->pageMeta['property'] = array_merge(
                Hash::get(
                    $defaultMeta,
                    'property',
                    []
                ),
                $this->pageMeta['property']
            );
        }
        // ogp:url の自動設定
        $this->pageMeta['property']['og:url'] = sprintf(
            '%s%s',
            Configure::read('environment.public_root_url'),
            $this->request->getUri()->getPath()
        );

        $isMobile = $isTablet = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $isMobile = $this->request->is('mobile');
            $isTablet = $this->request->is('tablet');
        }
        $this->set(compact('isMobile', 'isTablet'));
        $this->set('pageMeta', $this->pageMeta);
        // iframeにてページを埋め込むことを禁止する。
        $this->response->withHeader('X-FRAME-OPTIONS', ' SAMEORIGIN');
    }

    /**
     * recordProcessTimeLog method
     *
     * @param string $memo
     *
     * @return bool
     */
    public function recordProcessTimeLog($memo = 'default')
    {
        $processStartTimePoint = $this->getProcessStartTimePoint(microtime(true));
        if (empty($processStartTimePoint)) {
            $this->log('recordProcessTimeLog: 開始時間がセットされていません', 'info');

            return false;
        }
        $processId = $this->getProcessId();
        $processTime = microtime(true) - $processStartTimePoint;
        $this->log('(' . $processId . ')' . '処理時間：' . round($processTime, 3) . '秒 /' . $memo, 'info');

        return true;
    }

    /**
     * getProcessStartTimePoint method
     *
     * @return \Cake\Datasource\RepositoryInterface|null
     */
    public function getProcessStartTimePoint()
    {
        return $this->processStartTimePoint;
    }

    /**
     * getProcessId method
     *
     * @return mixed
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * setProcessId method
     *
     * @param $id
     */
    public function setProcessId($id)
    {
        $this->processId = $id;
    }

    /**
     * afterFilter method
     *
     * @param EventInterface $event
     *
     * @return \Cake\Http\Response|void|null
     */
    public function afterFilter(EventInterface $event)
    {
        if (Configure::read('record_process_time')) {
            $this->recordProcessTimeLog('at afterFilter');
        }
    }

    /**
     * beforeFilter method
     *
     * @param EventInterface $event
     *
     * @return \Cake\Http\Response|void|null
     */
    public function beforeFilter(EventInterface $event)
    {
        if (Configure::read('record_process_time')) {
            $this->setProcessId(mt_rand(0, 999));
            $processId = $this->getProcessId();
            $this->setProcessStartTimePoint(microtime(true));
            $this->log('(' . $processId . ')-- Request：' . $this->request->getRequestTarget(), 'info');
        }

        $authUser = $this->Auth->user();

        $this->Auth->allow(['controller' => 'pages', 'action' => 'display']);
        $query = $this->request->getQuery();
        //代理店から来た場合セッションにIDを保存
        if (isset($query['agcode']) && isset($query['uid'])) {
            //代理店コードが合っているかどうか判定
            $agency = TableRegistry::getTableLocator()->get('Agency')->find(
                'all',
                [
                'conditions' => [
                    'code' => $query['agcode'],
                    'is_active' => true,
                ],
                'recursive' => -1,
                ]
            )->first();

            if ($agency) {
                $agency = $agency->toArray();
                $this->request->getSession()->write('agency.id', $agency['id']);
                $this->request->getSession()->write('agency.name', $agency['name']);
                $this->request->getSession()->write('agency.slug', $agency['slug']);
                $this->request->getSession()->write('agency.discount_period', $agency['discount_period']);
                $this->request->getSession()->write('agency.uid', $query['uid']);
                if (isset($query['subcode'])) {
                    $this->request->getSession()->write('agency.subcode', $query['subcode']);
                }
            }
        }

        if (isset($query['c_code'])) {
            //チャネルコードがあっているかどうか判定
            $channel = TableRegistry::getTableLocator()->get('Channel')->find(
                'all',
                [
                'conditions' => [
                    'code' => $query['c_code'],
                ],
                'recursive' => -1,
                ]
            )->first();
            if ($channel) {
                $channel = $channel->toArray();
                $this->request->getSession()->write('channel_id', $channel['id']);
                if (isset($query['c_subcode'])) {
                    $this->request->getSession()->write('channel_subcode', $query['c_subcode']);
                }
            }
        }

        // 古いshorten_urlを持ったユーザーは紹介代理店経由になるようにURLを変更してリダイレクト
        if ((isset($query['referral']) && isset($query['referral_code'])) || (isset($query['agcode']) && $query['agcode'] === 'referral')) {
            $code = (isset($query['referral_code'])) ? $query['referral_code'] : $query['code'];
            $url = Configure::read('environment.private_root_url') . "/?c_code=referral&code=" . $code;

            return $this->redirect($url);
        }

        //紹介コードから来た場合セッションにIDを保存
        if (isset($query['c_code']) && isset($query['code'])) {
            $referralDone = $this->request->getSession()->read('referral_done');
            if (!isset($referralDone) && $query['c_code'] === 'referral') {
                // CookieにユーザーIDがない場合のみ紹介と認める
                if (!$this->Cookie->check('UID')) {
                    $this->request->getSession()->write('c_code', $query['c_code']);
                    $referralCode = $query['code'];
                    $this->request->getSession()->write('referral_code', $referralCode);
                } else {
                    $this->Flash->errorHeader(__('同じ端末(PC,スマホ等) からの新規登録は、紹介キャンペーンが適用されませんのでご注意ください。※ご友人様自身の端末から登録してください。'));
                    $this->request->getSession()->write('referral_done', 1);
                }
            } else {
                $this->Flash->errorHeader(__('同じ端末(PC,スマホ等) からの新規登録は、紹介キャンペーンが適用されませんのでご注意ください。※ご友人様自身の端末から登録してください。'));
                $this->request->getSession()->write('referral_done', 1);
            }
        }
        //ユーザーIDからFuncテーブルのデータを取得しセッションに保存
        $preAccessDate = $this->request->getSession()->read('lastAccessDate');
        $funcs = $this->request->getSession()->read('funcs');
        $today = date("Y-m-d");

        if ($authUser && ($preAccessDate != $today || empty($preAccessDate) || empty($funcs))) {
            $this->request->getSession()->write('lastAccessDate', $today);
            $contracts = TableRegistry::getTableLocator()->get('Contract')->find('all', [
                'conditions' => [
                    'Contract.user_id' => $authUser['id'],
                    'Contract.start_date <=' => $today,
                    'Contract.end_date >=' => $today,
                    'Contract.is_stopped' => 0,
                ],
                'contain' => [
                    'Plan' => [
                        'fields' => ['id'],
                        'Func' => [
                            'fields' => ['Func.id', 'Func.name'],
                        ],
                    ],
                ],
                'fields' => ['user_id', 'plan_id', 'start_date', 'end_date', 'is_stopped'],
            ])->first();
            $funcs = [];
            if (!empty($contracts)) {
                $funcs = Hash::combine($contracts->toArray(), 'plan.func.{n}.id', 'plan.func.{n}.name');
            }
            $this->request->getSession()->write('funcs', $funcs);
            $userFromDb = TableRegistry::getTableLocator()->get('User')->findById(
                $authUser['id'], // user_id
                null, // fields
                null, // order
                -1 // recursive
            )->first();
            $this->request->getSession()->write(
                'Auth.User',
                $userFromDb->toArray()
            );
        }
        if (!$authUser && (!empty($preAccessDate) || !empty($funcs))) {
            $this->request->getSession()->delete('funcs');
            $this->request->getSession()->delete('lastAccessDate');
        }

        // 流入元情報をCookieに書き込む
        $this->UserAnalysisData->writeCookieRefererInfo($authUser);
    }

    /**
     * setProcessStartTimePoint method
     *
     * @param $timePoint
     */
    public function setProcessStartTimePoint($timePoint)
    {
        $this->processStartTimePoint = $timePoint;
    }
}
