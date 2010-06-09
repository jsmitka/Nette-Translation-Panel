<?php
/*
 * Copyright (c) 2010 Jan Smitka <jan@smitka.org>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 */

/*namespace Nette\Addons;*/

/*use Nette\Environment;*/
/*use Nette\Web\Html;*/

/**
 * Panel for Nette DebugBar, which enables you to translate strings
 * directly from your browser.
 *
 * @author Jan Smitka <jan@smitka.org>
 */
class TranslationPanel implements /*\Nette\*/IDebugPanel
{
	/* Layout constants */
	const LAYOUT_HORIZONTAL = 1;
	const LAYOUT_VERTICAL = 2;


	/** @var IEditableTranslator */
	protected $translator;

	/** @var int TranslationPanel layout */
	protected $layout = self::LAYOUT_HORIZONTAL;

	/** @var int Height of the editor */
	protected $height = 300;

	

	public function __construct(IEditableTranslator $translator, $layout = NULL, $height = NULL)
	{
		$this->translator = $translator;

		if ($height !== NULL) {
			if (!is_numeric($height))
				throw new /*\*/InvalidArgumentException('Panel height has to be a numeric value.');
			$this->height = $height;
		}

		if ($layout !== NULL) {
			$this->layout = $layout;
			if ($height === NULL)
				$this->height = 500;
		}

		Environment::getApplication()->onStartup[] = callback($this, 'processRequest');
	}

	

	/**
	 * Return's panel ID.
	 * @return string
	 */
	public function getId()
	{
		return 'TranslationPanel';
	}


	/**
	 * Returns the code for the panel tab.
	 * @return string
	 */
	public function getTab()
	{
		$tab = Html::el('span');
		$image = $tab->create('img')->src($this->getIconSrc('flag_blue.png'))->id('TranslationPanel-icon');
		$tab->add('Translations');
		$tab->create('script')->type('text/javascript')->setHtml('translationPanel.init();');
		return (string) $tab;
	}


	/**
	 * Returns the code for the panel itself.
	 * @return string
	 */
	public function getPanel()
	{
		$translator = $this->translator;
		$strings = $this->translator->getStrings();

		if (Environment::getSession()->isStarted()) {
			$session = Environment::getSession('Nette.Addons.TranslationPanel');
			$untranslatedStack = isset($session['stack']) ? $session['stack'] : array();
			foreach ($strings as $string => $data) {
				if (!$data) {
					$untranslatedStack[$string] = FALSE;
				}
			}
			$session['stack'] = $untranslatedStack;

			foreach ($untranslatedStack as $string => $value) {
				if (!isset($strings[$string]))
					$strings[$string] = FALSE;
			}
		}

		ob_start();
		require dirname(__FILE__) . '/TranslationPanel.panel.phtml';
		return ob_get_clean();
	}


	/**
	 * Handles an incomuing request and saves the data if necessary.
	 */
	public function processRequest()
	{
		// Try starting the session
		try {
			$session = Environment::getSession('Nette.Addons.TranslationPanel');
		} catch (InvalidStateException $e) {
			$session = FALSE;
		}

		$request = Environment::getHttpRequest();
		if ($request->isPost() && $request->isAjax() && $request->getHeader('X-Translation-Client')) {
			$data = json_decode(file_get_contents('php://input'));
			if ($data) {
				if ($session) {
					$stack = isset($session['stack']) ? $session['stack'] : array();
				}

				foreach ($data as $string => $value) {
					$this->translator->setTranslation($string, $value);
					if ($session && isset($stack[$string]))
						unset($stack[$string]);
				}
				$this->translator->save();

				if ($session)
					$session['stack'] = $stack;
			}
		}
	}



	/**
	 * Returns an embedded PNG icon.
	 * @param string $icon
	 * @return string
	 */
	protected function getIconSrc($icon)
	{
		return 'data:image/png;base64,' . base64_encode(file_get_contents(dirname(__FILE__) . '/icons/' . $icon));
	}


	/**
	 * Return an odrdinal number suffix.
	 * @param string $count
	 * @return string
	 */
	protected function ordinalSuffix($count)
	{
		switch (substr($count, -1)) {
			case '1':
				return 'st';
			case '2':
				return 'nd';
			case '3':
				return 'rd';
			default:
				return 'th';
		}
	}
}