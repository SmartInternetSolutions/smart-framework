<?php

class SF_Model_Mail extends Zend_Mail
{
    /**
     * Mail character set
     * @var string
     */
    protected $_charset = 'utf-8';

    protected $_blockHtml = null;

    public function getBlockHtml()
    {
        return $this->_blockHtml;
    }

    public function setBlockHtml(SF_Block_Abstract $block)
    {
        $this->_blockHtml = $block;

        return $this;
    }

    protected $_blockText = null;

    public function getBlockText()
    {
        return $this->_blockText;
    }

    public function setBlockText(SF_Block_Abstract $block)
    {
        $this->_blockText = $block;

        return $this;
    }

    protected $_cidFnMap = array();

    public function getAttachmentUrlByFilename($fn)
    {
        return isset($this->_cidFnMap[$fn]) ? 'cid:' . $this->_cidFnMap[$fn] : null;
    }

    public function attachInlineFile($filename)
    {
        if (isset($this->_cidFnMap[$filename])) {
            return $this;
        }

        $id = uniqid('fn', true) . '-' . abs(time() ^ 18357183951);

        $this->_cidFnMap[$filename] = $id;

        $this->setType(Zend_Mime::MULTIPART_RELATED);

        $rfn = BASEDIR . '/' . $filename;

        $at = $this->createAttachment(file_get_contents($rfn));
        $at->type = $this->mimeByExtension($rfn);
        $at->disposition = Zend_Mime::DISPOSITION_INLINE;
        $at->encoding = Zend_Mime::ENCODING_BASE64;
        $at->id = $id;

        return $this;
    }

    public function mimeByExtension($filename)
    {
        if (is_readable($filename) ) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            switch ($extension) {
                case 'gif':
                    $type = 'image/gif';
                    break;
                case 'jpg':
                case 'jpeg':
                    $type = 'image/jpg';
                    break;
                case 'png':
                    $type = 'image/png';
                    break;
                default:
                    $type = 'application/octet-stream';
            }

            return $type;
        }

        return 'application/octet-stream';
    }

    public function send($transport = null)
    {
        $this->_charset = 'UTF-8';

        if ($this->_blockHtml !== null) {
            $this->setBodyHtml($this->_blockHtml->toHtml(), $this->_charset);
        }

        if ($this->_blockText !== null) {
            $this->setBodyText($this->_blockText->toHtml(), $this->_charset);
        } else {
            // FIXME
            if ($this->_blockHtml instanceof SF_Block_Abstract) {
                $this->setBodyText($this->_blockHtml->toText());
            } else {
                $trimmedText = $this->_blockHtml->toHtml();

                // remove title
                $trimmedText = preg_replace('/<title>[^>]+<\/title>/mU', '', $trimmedText);

                $trimmedText = strip_tags($trimmedText, $this->_charset);

                // remove unneccessary whitespaces
                $trimmedText = preg_replace('/\s\s+/m', '', $trimmedText);

                $trimmedText = preg_replace('/\n\n+/m', '', $trimmedText);

                $this->setBodyText($trimmedText);
            }
        }

        $this->setMessageId();

        return parent::send($transport);
    }

    protected $_lastTo = '';

    public function addTo($email, $name = '')
    {
        $this->_lastTo = $email;

        return parent::addTo($email, $name);
    }

    public function getTo()
    {
        return $this->_lastTo;
    }
}