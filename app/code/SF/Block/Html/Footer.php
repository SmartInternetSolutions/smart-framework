<?php

class SF_Block_Html_Footer extends SF_Block_Html
{
    protected $_template = 'html/footer.phtml';

    protected $_jsFiles = array();

	public function addJsFiles($js)
	{
		if (!is_array($js)) {
			$js = array($js);
		}

		foreach($js as $file) {
			if (!in_array($file, $this->_jsFiles)) {
				$this->_jsFiles[] = $file;
			}
		}

		return $this;
	}

	public function getJsFiles()
	{
		return $this->_jsFiles;
	}

    /**
     * adds requested resources to rendering data for footer
     *
     * @since 1.1.2
     */
    protected function _prepare() {
        parent::_prepare();

        $this->addJsFiles(self::_getResourceSet('js'));
    }
}

