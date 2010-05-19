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



/**
 * Panel for Nette DebugBar, which enables you to translate strings
 * directly from your browser.
 *
 * @author Jan Smitka <jan@smitka.org>
 */
class TranslationPanel implements IDebugPanel
{
	/** @var IEditableTranslator */
	protected $translator;

	

	public function __construct(IEditableTranslator $translator)
	{
		$this->translator = $translator;

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
		$tab = Html::el();
		$tab->create('img')->src($this->getIconSrc('flag_blue.png'));
		$tab->add('Translations');
		return (string) $tab;
	}


	/**
	 * Returns the code for the panel itself.
	 * @return string
	 */
	public function getPanel()
	{
		$translator = $this->translator;

		ob_start();
		require dirname(__FILE__) . '/TranslationPanel.panel.phtml';
		return ob_get_clean();
	}


	/**
	 * Handles an incomuing request and saves the data if necessary.
	 */
	public function processRequest()
	{
		$request = Environment::getHttpRequest();
		if ($request->isPost() && $request->isAjax() && $request->getHeader('X-Translation-Client')) {
			$data = json_decode(file_get_contents('php://input'));
			if ($data) {
				foreach ($data as $string => $value) {
					$this->translator->setTranslation($string, $value);
				}
				$this->translator->save();
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