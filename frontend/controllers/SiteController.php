<?php
namespace frontend\controllers;

use app\models\forms\SignupForm;
use Yii;
use common\models\forms\LoginForm;
use frontend\models\forms\PasswordResetRequestForm;
use frontend\models\forms\ResetPasswordForm;
use frontend\models\forms\ContactForm;
use yii\base\InvalidParamException;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Site controller
 */
class SiteController extends Controller {

	/**
	 * @inheritdoc
	 */
	public function behaviors () {
		return [
			'access' => [
				'class' => AccessControl::className(),
				'only'  => [
					'logout',
					'signup',
					'request-password-reset',
					'reset-password',
				],
				'rules' => [
					[
						'actions' => [
							'signup',
							'request-password-reset',
							'reset-password'
						],
						'allow'   => true,
						'roles'   => ['?'],
					],
					[
						'actions' => ['logout'],
						'allow'   => true,
						'roles'   => ['@'],
					],
				],
			],
			'verbs'  => [
				'class'   => VerbFilter::className(),
				'actions' => [
					'logout' => ['post'],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function actions () {
		return [
			'error'   => [
				'class' => 'yii\web\ErrorAction',
			],
			'captcha' => [
				'class'           => 'yii\captcha\CaptchaAction',
				'fixedVerifyCode' => YII_ENV_TEST
					?'testme'
					:null,
			],
		];
	}

	public function actionIndex () {
		return $this->render('index');
	}

	public function actionLogin () {
		if (!\Yii::$app->user->isGuest) {
			return $this->goHome();
		}
		$model = new LoginForm();
		if ($model->load(Yii::$app->request->post()) && $model->login()) {
			return $this->goBack();
		} else {
			return $this->render(
				'login',
				[
					'model' => $model,
				]
			);
		}
	}

	public function actionLogout () {
		Yii::$app->user->logout();
		return $this->goHome();
	}

	public function actionContact () {
		$model = new ContactForm();
		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
				Yii::$app->session->setFlash(
					'success',
					'Thank you for contacting us. We will respond to you as soon as possible.'
				);
			} else {
				Yii::$app->session->setFlash('error', 'There was an error sending email.');
			}
			return $this->refresh();
		} else {
			return $this->render(
				'contact',
				[
					'model' => $model,
				]
			);
		}
	}

	public function actionAbout () {
		return $this->render('about');
	}

	public function actionSignup () {
		$model = new SignupForm();
		if ($model->load(Yii::$app->request->post())) {
			if ($user = $model->signup()) {
				if (Yii::$app->getUser()->login($user)) {
					return $this->goHome();
				}
			}
		}
		return $this->render(
			'signup',
			[
				'model' => $model,
			]
		);
	}

	public function actionRequestPasswordReset () {
		$model = new PasswordResetRequestForm();
		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			if ($model->sendEmail()) {
				Yii::$app->getSession()->setFlash('success', 'Проверьте ваш e-mail для дальнейших инструкций.');
				return $this->goHome();
			} else {
				Yii::$app->getSession()->setFlash(
					'error',
					'Извините, мы не можем сбросить пароль по указанному e-mail.'
				);
			}
		}
		return $this->render(
			'requestPasswordReset',
			[
				'model' => $model,
			]
		);
	}

	public function actionResetPassword ($token) {
		try {
			$model = new ResetPasswordForm($token);
		} catch(InvalidParamException $e) {
			throw new BadRequestHttpException($e->getMessage());
		}
		if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
			Yii::$app->getSession()->setFlash('success', 'Новый пароль был сохранен.');
			return $this->goHome();
		}
		return $this->render(
			'resetPassword',
			[
				'model' => $model,
			]
		);
	}

}
