<?php

namespace NatureQuizzer\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Nextras\Datagrid\Datagrid;
use SplFileInfo;


class ExportPresenter extends BasePresenter
{
	protected $resource = 'exports';

	public function startup()
	{
		$this->perm();
		parent::startup();
	}

	private function getExportDirectory()
	{
		return realpath(__DIR__ . '/../../exports');
	}

	public function actionDefault()
	{

	}

	public function actionAdd()
	{
		$baseDir = $this->getExportDirectory();
		exec(sprintf('cd %s && php ../utils/export-data.php export .', $baseDir), $output);
		$this->flashMessage('The export was performed: ' . nl2br(implode("\n", $output)), 'success');
		$this->redirect('default');
	}

	public function actionDownload($name)
	{
		$baseDir = $this->getExportDirectory();
		$path = realpath($baseDir . '/' . $name);
		if (!$path || !Strings::startsWith($path, $baseDir)) {
			throw new BadRequestException('No such backup.');
		}
		$this->sendResponse(new FileResponse(($path)));
	}

	public function createComponentExportList()
	{
		$grid = new Datagrid();
		$grid->setRowPrimaryKey('name');
		$grid->setDatasourceCallback(function ($filter, $order) {
			$files = Finder::findFiles('*.tar.gz')->in($this->getExportDirectory());
			$output = [];
			/** @var SplFileInfo $file */
			foreach ($files as $file) {
				$temp = new \stdClass;
				$temp->name = $file->getBasename();
				$temp->size = ceil($file->getSize() / 1024 / 1024) . ' MB';
				$output[$file->getBasename()] =  $temp;
			}
			krsort($output);
			return $output;
		});

		$grid->addCellsTemplate(__DIR__ . '/../grids/export-list.latte');
		$grid->addColumn('name', 'Name');
		$grid->addColumn('size', 'Size');
		return $grid;
	}

}
