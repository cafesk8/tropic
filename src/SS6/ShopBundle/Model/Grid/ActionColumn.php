<?php

namespace SS6\ShopBundle\Model\Grid;

use SS6\ShopBundle\Component\Router\Security\RouteCsrfProtector;
use SS6\ShopBundle\Model\Grid\Grid;
use Symfony\Component\Routing\Router;

class ActionColumn {

	const TYPE_DELETE = 'delete';
	const TYPE_EDIT = 'edit';

	/**
	 * @var \Symfony\Component\Routing\Router
	 */
	private $router;

	/**
	 * @var RouteCsrfProtector
	 */
	private $routeCsrfProtector;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $route;

	/**
	 * @var array
	 */
	private $bindingRouteParams;

	/**
	 * @var array
	 */
	private $additionalRouteParams;

	/**
	 * @var string|null
	 */
	private $classAttributte;

	/**
	 * @var string|null
	 */
	private $confirmMessage;

	/**
	 * @var bool
	 */
	private $isAjaxConfirm;

	/**
	 * @param \Symfony\Component\Routing\Router $router
	 * @param string $type
	 * @param string $name
	 * @param string $route
	 * @param array $bindingRouteParams
	 * @param array $additionalRouteParams
	 */
	public function __construct(
		Router $router,
		RouteCsrfProtector $routeCsrfProtector,
		$type,
		$name,
		$route,
		array $bindingRouteParams,
		array $additionalRouteParams
	) {
		$this->router = $router;
		$this->routeCsrfProtector = $routeCsrfProtector;
		$this->type = $type;
		$this->name = $name;
		$this->route = $route;
		$this->bindingRouteParams = $bindingRouteParams;
		$this->additionalRouteParams = $additionalRouteParams;
		$this->isAjaxConfirm = false;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getClassAttribute() {
		return $this->classAttributte;
	}

	/**
	 * @return string|null
	 */
	public function getConfirmMessage() {
		return $this->confirmMessage;
	}

	/**
	 * @param string $classAttribute
	 * @return \SS6\ShopBundle\Model\Grid\ActionColumn
	 */
	public function setClassAttribute($classAttribute) {
		$this->classAttributte = $classAttribute;

		return $this;
	}

	/**
	 * @param string $confirmMessage
	 * @return \SS6\ShopBundle\Model\Grid\ActionColumn
	 */
	public function setConfirmMessage($confirmMessage) {
		$this->confirmMessage = $confirmMessage;

		return $this;
	}

	/**
	 * @return \SS6\ShopBundle\Model\Grid\ActionColumn
	 */
	public function setAjaxConfirm() {
		$this->isAjaxConfirm = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAjaxConfirm() {
		return $this->isAjaxConfirm;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getTargetUrl(array $row) {
		$parameters = $this->additionalRouteParams;

		foreach ($this->bindingRouteParams as $key => $sourceColumnName) {
			$parameters[$key] = Grid::getValueFromRowBySourceColumnName($row, $sourceColumnName);
		}

		if ($this->type === self::TYPE_DELETE) {
			$parameters[RouteCsrfProtector::CSRF_TOKEN_REQUEST_PARAMETER] = $this->routeCsrfProtector->getCsrfTokenByRoute($this->route);
		}

		return $this->router->generate($this->route, $parameters, Router::ABSOLUTE_URL);
	}

}
