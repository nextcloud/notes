<?php
/**
 * Nextcloud - Notes
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\Notes\Service;

use OCP\Files\FileInfo;
use OCP\IL10N;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OCP\ILogger;
use OC\Encryption\Exceptions\DecryptionFailedException;
use League\Flysystem\FileNotFoundException;
use OCA\Notes\Db\Note;

/**
 * Class NotesService
 *
 * @package OCA\Notes\Service
 */
class NotesService {

    private $l10n;
    private $root;
    private $logger;
    private $appName;

    /**
     * @param IRootFolder $root
     * @param IL10N $l10n
     * @param ILogger $logger
     * @param String $appName
     */
    public function __construct (IRootFolder $root, IL10N $l10n, ILogger $logger, $appName) {
        $this->root = $root;
        $this->l10n = $l10n;
        $this->logger = $logger;
        $this->appName = $appName;
    }


    /**
     * @param string $userId
     * @return array with all notes in the current directory
     */
    public function getAll ($userId){
        $notesFolder = $this->getFolderForUser($userId);
        $notes = $this->gatherNoteFiles($notesFolder);
        $filesById = [];
        foreach($notes as $note) {
            $filesById[$note->getId()] = $note;
        }
        $tagger = \OC::$server->getTagManager()->load('files');
        if($tagger===null) {
            $tags = [];
        } else {
            $tags = $tagger->getTagsForObjects(array_keys($filesById));
        }

        $notes = [];
        foreach($filesById as $id=>$file) {
            $notes[] = $this->getNote($file, $notesFolder, array_key_exists($id, $tags) ? $tags[$id] : []);
        }

        return $notes;
    }


    /**
     * Used to get a single note by id
     * @param int $id the id of the note to get
     * @param string $userId
     * @throws NoteDoesNotExistException if note does not exist
     * @return Note
     */
    public function get ($id, $userId) {
        $folder = $this->getFolderForUser($userId);
        return $this->getNote($this->getFileById($folder, $id), $folder, $this->getTags($id));
    }

    private function getTags ($id) {
        $tagger = \OC::$server->getTagManager()->load('files');
        if($tagger===null) {
            $tags = [];
        } else {
            $tags = $tagger->getTagsForObjects([$id]);
        }
        return array_key_exists($id, $tags) ? $tags[$id] : [];
    }
    private function getNote($file,$notesFolder,$tags=[]){

        $id=$file->getId();

        try{
            $note=Note::fromFile($file, $notesFolder, $tags);
        }catch(FileNotFoundException $e){
            $note = Note::fromException($this->l10n->t('File error').': ('.$file->getName().') '.$e->getMessage(), $file, $notesFolder, array_key_exists($id, $tags) ? $tags[$id] : []);
        }catch(DecryptionFailedException $e){
            $note = Note::fromException($this->l10n->t('Encryption Error').': ('.$file->getName().') '.$e->getMessage(), $file, $notesFolder, array_key_exists($id, $tags) ? $tags[$id] : []);
        }catch(\Exception $e){
            $note = Note::fromException($this->l10n->t('Error').': ('.$file->getName().') '.$e->getMessage(), $file, $notesFolder, array_key_exists($id, $tags) ? $tags[$id] : []);
        }
        return $note;
    }
    /**
     * Creates a note and returns the empty note
     * @param string $userId
     * @see update for setting note content
     * @return Note the newly created note
     */
    public function create ($userId) {
        $title = $this->l10n->t('New note');
        $folder = $this->getFolderForUser($userId);

        // check new note exists already and we need to number it
        // pass -1 because no file has id -1 and that will ensure
        // to only return filenames that dont yet exist
        $path = $this->generateFileName($folder, $title, "txt", -1);
        $file = $folder->newFile($path);

        return $this->getNote($file, $folder);
    }


    /**
     * Updates a note. Be sure to check the returned note since the title is
     * dynamically generated and filename conflicts are resolved
     * @param int $id the id of the note used to update
     * @param string $content the content which will be written into the note
     * the title is generated from the first line of the content
     * @param int $mtime time of the note modification (optional)
     * @throws NoteDoesNotExistException if note does not exist
     * @return \OCA\Notes\Db\Note the updated note
     */
    public function update ($id, $content, $userId, $category=null, $mtime=0) {
        $notesFolder = $this->getFolderForUser($userId);
        $file = $this->getFileById($notesFolder, $id);
        $folder = $file->getParent();
        $title = $this->getSafeTitleFromContent($content);


        // rename/move file with respect to title/category
        // this can fail if access rights are not sufficient or category name is illegal
        try {
            $currentFilePath = $this->root->getFullPath($file->getPath());
            $fileExtension = pathinfo($file->getName(), PATHINFO_EXTENSION);

            // detect (new) folder path based on category name
            if($category===null) {
                $basePath = pathinfo($currentFilePath, PATHINFO_DIRNAME);
            } else {
                $basePath = $notesFolder->getPath();
                if(!empty($category)) {
                    // sanitise path
                    $cats = explode('/', $category);
                    $cats = array_map([$this, 'sanitisePath'], $cats);
                    $cats = array_filter($cats, function($str) { return !empty($str); });
                    $basePath .= '/'.implode('/', $cats);
                }
                $this->getOrCreateFolder($basePath);
            }

            // assemble new file path
            $newFilePath = $basePath . '/' . $this->generateFileName($folder, $title, $fileExtension, $id);

            // if the current path is not the new path, the file has to be renamed
            if($currentFilePath !== $newFilePath) {
                $file->move($newFilePath);
            }
        } catch(\OCP\Files\NotPermittedException $e) {
            $this->logger->error('Moving note '.$id.' ('.$title.') to the desired target is not allowed. Please check the note\'s target category ('.$category.').', ['app' => $this->appName]);
        } catch(\Exception $e) {
            $this->logger->error('Moving note '.$id.' ('.$title.') to the desired target has failed with a '.get_class($e).': '.$e->getMessage(), ['app' => $this->appName]);
        }

        $file->putContent($content);

        if($mtime) {
            $file->touch($mtime);
        }

        return $this->getNote($file, $notesFolder, $this->getTags($id));
    }


    /**
     * Set or unset a note as favorite.
     * @param int $id the id of the note used to update
     * @param boolean $favorite whether the note should be a favorite or not
     * @throws NoteDoesNotExistException if note does not exist
     * @return boolean the new favorite state of the note
     */
    public function favorite ($id, $favorite, $userId){
        $folder = $this->getFolderForUser($userId);
        $file = $this->getFileById($folder, $id);
        if(!$this->isNote($file)) {
            throw new NoteDoesNotExistException();
        }
        $tagger = \OC::$server->getTagManager()->load('files');
        if($favorite)
            $tagger->addToFavorites($id);
        else
            $tagger->removeFromFavorites($id);

        $tags = $tagger->getTagsForObjects([$id]);
        return array_key_exists($id, $tags) && in_array(\OC\Tags::TAG_FAVORITE, $tags[$id]);
    }


    /**
     * Deletes a note
     * @param int $id the id of the note which should be deleted
     * @param string $userId
     * @throws NoteDoesNotExistException if note does not
     * exist
     */
    public function delete ($id, $userId) {
        $folder = $this->getFolderForUser($userId);
        $file = $this->getFileById($folder, $id);
        $file->delete();
    }

    // removes characters that are illegal in a file or folder name on some operating systems
    private function sanitisePath($str) {
        // remove characters which are illegal on Windows (includes illegal characters on Unix/Linux)
        // prevents also directory traversal by eliminiating slashes
        // see also \OC\Files\Storage\Common::verifyPosixPath(...)
        $str = str_replace(['*', '|', '/', '\\', ':', '"', '<', '>', '?'], '', $str);

        // if mysql doesn't support 4byte UTF-8, then remove those characters
        // see \OC\Files\Storage\Common::verifyPath(...)
        if (!\OC::$server->getDatabaseConnection()->supports4ByteText()) {
            $str = preg_replace('%(?:
                \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
              | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
              | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
              )%xs', '', $str);
        }

        // prevent file to be hidden
        $str = preg_replace("/^[\. ]+/mu", "", $str);
        return trim($str);
    }

    private function getSafeTitleFromContent($content) {
        // prepare content: remove markdown characters and empty spaces
        $content = preg_replace("/^\s*[*+-]\s+/mu", "", $content); // list item
        $content = preg_replace("/^#+\s+(.*?)\s*#*$/mu", "$1", $content); // headline
        $content = preg_replace("/^(=+|-+)$/mu", "", $content); // separate line for headline
        $content = preg_replace("/(\*+|_+)(.*?)\\1/mu", "$2", $content); // emphasis

        // sanitize: prevent directory traversal, illegal characters and unintended file names
        $content = $this->sanitisePath($content);

        // generate title from the first line of the content
        $splitContent = preg_split("/\R/u", $content, 2);
        $title = trim($splitContent[0]);

        // ensure that title is not empty
        if(empty($title)) {
            $title = $this->l10n->t('New note');
        }

        // using a maximum of 100 chars should be enough
        $title = mb_substr($title, 0, 100, "UTF-8");

        return $title;
    }

    /**
     * @param Folder $folder
     * @param int $id
     * @throws NoteDoesNotExistException
     * @return \OCP\Files\File
     */
    private function getFileById ($folder, $id) {
        $file = $folder->getById($id);

        if(count($file) <= 0 || !$this->isNote($file[0])) {
            throw new NoteDoesNotExistException();
        }
        return $file[0];
    }


    /**
     * @param string $userId the user id
     * @return Folder
     */
    private function getFolderForUser ($userId) {
        $path = '/' . $userId . '/files/Notes';
        return $this->getOrCreateFolder($path);
    }


    /**
     * Finds a folder and creates it if non-existent
     * @param string $path path to the folder
     * @return Folder
     */
    private function getOrCreateFolder($path) {
        if ($this->root->nodeExists($path)) {
            $folder = $this->root->get($path);
        } else {
            $folder = $this->root->newFolder($path);
        }
        return $folder;
    }


    /**
     * get path of file and the title.txt and check if they are the same
     * file. If not the title needs to be renamed
     *
     * @param Folder $folder a folder to the notes directory
     * @param string $title the filename which should be used
     * @param string $extension the extension which should be used
     * @param int $id the id of the note for which the title should be generated
     * used to see if the file itself has the title and not a different file for
     * checking for filename collisions
     * @return string the resolved filename to prevent overwriting different
     * files with the same title
     */
    private function generateFileName (Folder $folder, $title, $extension, $id) {
        $path = $title . '.' . $extension;

        // if file does not exist, that name has not been taken. Similar we don't
        // need to handle file collisions if it is the filename did not change
        if (!$folder->nodeExists($path) || $folder->get($path)->getId() === $id) {
            return $path;
        } else {
            // increments name (2) to name (3)
            $match = preg_match('/\((?P<id>\d+)\)$/u', $title, $matches);
            if($match) {
                $newId = ((int) $matches['id']) + 1;
                $newTitle = preg_replace('/(.*)\s\((\d+)\)$/u',
                    '$1 (' . $newId . ')', $title);
            } else {
                $newTitle = $title . ' (2)';
            }
            return $this->generateFileName($folder, $newTitle, $extension, $id);
        }
    }

	/**
	 * gather note files in given directory and all subdirectories
	 * @param Folder $folder
	 * @return array
	 */
	private function gatherNoteFiles ($folder) {
		$notes = [];
		$nodes = $folder->getDirectoryListing();
		foreach($nodes as $node) {
			if($node->getType() === FileInfo::TYPE_FOLDER) {
				$notes = array_merge($notes, $this->gatherNoteFiles($node));
				continue;
			}
			if($this->isNote($node)) {
				$notes[] = $node;
			}
		}
		return $notes;
	}


    /**
     * test if file is a note
     *
     * @param \OCP\Files\File $file
     * @return bool
     */
    private function isNote($file) {
        $allowedExtensions = ['txt', 'org', 'markdown', 'md', 'note'];

        if($file->getType() !== 'file') return false;
        if(!in_array(
            pathinfo($file->getName(), PATHINFO_EXTENSION),
            $allowedExtensions
        )) return false;

        return true;
    }

}
