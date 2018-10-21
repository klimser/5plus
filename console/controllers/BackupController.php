<?php

namespace console\controllers;

use yii;
use yii\console\Controller;

/**
 * BackupController is used to store DB backup to Google drive.
 */
class BackupController extends Controller
{
    /**
     * Creates backup.
     * @return int
     */
    public function actionCreate()
    {
        $recordsPerInsert = Yii::$app->params['backupMaxRecordsPerInsert'];
        $cmd = Yii::$app->db->createCommand('SHOW TABLES');
        $tables = $cmd->queryColumn();

        $sqlFilename = Yii::$app->runtimePath . DIRECTORY_SEPARATOR . 'backup.sql';
        $gzipFilename = Yii::$app->runtimePath . DIRECTORY_SEPARATOR . 'backup.sql.gz';

        $fp = fopen($sqlFilename, 'w');
        foreach ($tables as $table) {
            $cmd = Yii::$app->db->createCommand("SHOW CREATE TABLE `$table`");
            $res = $cmd->queryOne();
            $createQuery = $res['Create Table'];
            $createQuery = preg_replace('#^CREATE TABLE#', 'CREATE TABLE IF NOT EXISTS', $createQuery);
            $createQuery = preg_replace ('#AUTO_INCREMENT\s*=\s*([0-9])+#', '', $createQuery);
            fputs($fp, "$createQuery;\n\n");

            $offset = 0;
            $itemsFilled = false;
            do {
                $cmd = Yii::$app->db->createCommand("SELECT * FROM `$table` LIMIT $recordsPerInsert OFFSET $offset");
                $results = $cmd->queryAll();
                foreach ($results as $result) {
                    if (!$itemsFilled) {
                        $itemNames = array_keys($result);
                        array_walk($itemNames, function (&$val) {$val = addslashes($val);});
                        $items = implode('`,`', $itemNames);
                        fputs($fp,"INSERT INTO `$table` (`$items`) VALUES \n");
                        $itemsFilled = true;
                    } else fputs($fp,", \n");

                    $itemValues = array_values($result);
                    array_walk($itemValues, function (&$val) {$val = addslashes($val);});
                    fputs($fp,"('" . implode("','", $itemValues) . "')");
                }
                $offset += $recordsPerInsert;
            } while (!empty($results));
            fputs($fp, ";\n\n");
        }
        fclose($fp);

        if ($fpGzip = gzopen($gzipFilename, 'wb9')) {
            if ($fp = fopen($sqlFilename,'rb')) {
                while (!feof($fp)) {
                    gzwrite($fpGzip, fread($fp, 1024 * 512));
                }
                fclose($fp);
                unlink($sqlFilename);
            }
            gzclose($fpGzip);
        }

        if (array_key_exists('google_auth_data', Yii::$app->params)) {
            $tokenFilename = Yii::$app->runtimePath . DIRECTORY_SEPARATOR . 'google_token.json';

            $client = new \Google_Client();
            $client->setScopes([\Google_Service_Drive::DRIVE]);
            $client->setAuthConfig(Yii::$app->params['google_auth_data']);
            $client->setAccessType('offline');

            if (file_exists($tokenFilename)) {
                $accessToken = json_decode(file_get_contents($tokenFilename), true);
            } else {
                Yii::$app->errorLogger->logError('backup/create', 'No google drive credentials', true);
                return yii\console\ExitCode::OK;

//            $authUrl = $client->createAuthUrl();
//            printf("Open the following link in your browser:\n%s\n", $authUrl);
//            print 'Enter verification code: ';
//            $authCode = trim(fgets(STDIN));
//
//            // Exchange authorization code for an access token.
//            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
//
//            file_put_contents($tokenFilename, json_encode($accessToken));
//            printf("Credentials saved \n");
            }

            $client->setAccessToken($accessToken);

            // Refresh the token if it's expired.
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($tokenFilename, json_encode($client->getAccessToken()));
            }

            $driveService = new \Google_Service_Drive($client);

            $backupsFolder = $driveService->files->listFiles(['q' => 'name=\'backups\'']);
            if ($backupsFolder->count() == 0) {
                $fileMetadata = new \Google_Service_Drive_DriveFile([
                    'name' => 'backups',
                    'mimeType' => 'application/vnd.google-apps.folder'
                ]);
                $folder = $driveService->files->create($fileMetadata, ['fields' => 'id']);
                $backupsFolderId = $folder->id;
            } else {
                $backupsFolderId = $backupsFolder->current()->id;
            }

            $backupFolderName = Yii::$app->params['backupFolderName'];
            $backupFolder = $driveService->files->listFiles(['q' => "name = '$backupFolderName' and '$backupsFolderId' in parents"]);
            if ($backupFolder->count() == 0) {
                $fileMetadata = new \Google_Service_Drive_DriveFile([
                    'name' => $backupFolderName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$backupsFolderId],
                ]);
                $folder = $driveService->files->create($fileMetadata, ['fields' => 'id']);
                $backupFolderId = $folder->id;
            } else {
                $backupFolderId = $backupFolder->current()->id;
            }

            $backupFileId = null;
            $backupFileName = 'db_backup_' . date('Y-m-d') . '.sql.gz';
            $backupFileMetadata = new \Google_Service_Drive_DriveFile([
                'name' => $backupFileName,
                'parents' => [$backupFolderId],
            ]);
            $backupFileData = [
                'data' => file_get_contents($gzipFilename),
                'mimeType' => 'application/x-gzip',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ];
            $backupFile = $driveService->files->listFiles(['q' => "name = '$backupFileName' and '$backupFolderId' in parents"]);
            if ($backupFile->count() == 0) {
                $backupFile = $driveService->files->create($backupFileMetadata, $backupFileData);
                if ($backupFile->id) $backupFileId = $backupFile->id;
            } else {
                $backupFileId = $backupFile->current()->id;
                $backupFileMetadata->setParents([]);
                $driveService->files->update($backupFileId, $backupFileMetadata, $backupFileData);
            }

            if ($backupFileId) unlink($gzipFilename);

            $backupFiles = $driveService->files->listFiles(['q' => "name contains 'db_backup_' and '$backupFolderId' in parents"]);
            $currentDate = new \DateTime();
            /** @var \Google_Service_Drive_DriveFile $file */
            foreach ($backupFiles->getFiles() as $file) {
                if (preg_match('#^db_backup_(\d{4}-\d{2}-\d{2})#', $file->name, $matches)) {
                    $backupDate = date_create_from_format('Y-m-d', $matches[1]);
                    if ($backupDate->diff($currentDate)->days > 30) {
                        $driveService->files->delete($file->id);
                    }
                }
            }
        }

        return yii\console\ExitCode::OK;
    }
}