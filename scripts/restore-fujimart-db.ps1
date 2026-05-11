param(
    [string]$DatabaseName = "fujimart_hrm_source",
    [string]$BackupPath = "/var/opt/mssql/backup/DUNGNTN_HRM.bak",
    [string]$ContainerName = "payroll-sqlserver"
)

$ErrorActionPreference = "Stop"

$password = $env:DB_PASSWORD
if ([string]::IsNullOrWhiteSpace($password)) {
    $password = "YourStrong!Passw0rd"
}

$sql = @"
SET NOCOUNT ON;

DECLARE @BackupPath nvarchar(4000) = N'$BackupPath';
DECLARE @DatabaseName sysname = N'$DatabaseName';
DECLARE @DataLogicalName sysname;
DECLARE @LogLogicalName sysname;
DECLARE @Sql nvarchar(max);

CREATE TABLE #BackupFiles (
    LogicalName nvarchar(128),
    PhysicalName nvarchar(260),
    [Type] char(1),
    FileGroupName nvarchar(128) NULL,
    Size numeric(20,0),
    MaxSize numeric(20,0),
    FileId bigint,
    CreateLSN numeric(25,0) NULL,
    DropLSN numeric(25,0) NULL,
    UniqueId uniqueidentifier,
    ReadOnlyLSN numeric(25,0) NULL,
    ReadWriteLSN numeric(25,0) NULL,
    BackupSizeInBytes bigint,
    SourceBlockSize int,
    FileGroupId int,
    LogGroupGUID uniqueidentifier NULL,
    DifferentialBaseLSN numeric(25,0) NULL,
    DifferentialBaseGUID uniqueidentifier NULL,
    IsReadOnly bit,
    IsPresent bit,
    TDEThumbprint varbinary(32) NULL,
    SnapshotUrl nvarchar(360) NULL
);

INSERT #BackupFiles EXEC(N'RESTORE FILELISTONLY FROM DISK = ''' + @BackupPath + N'''');

SELECT TOP (1) @DataLogicalName = LogicalName FROM #BackupFiles WHERE [Type] = 'D' ORDER BY FileId;
SELECT TOP (1) @LogLogicalName = LogicalName FROM #BackupFiles WHERE [Type] = 'L' ORDER BY FileId;

IF @DataLogicalName IS NULL OR @LogLogicalName IS NULL
    THROW 51000, 'Cannot identify data/log logical file names from backup.', 1;

IF DB_ID(@DatabaseName) IS NOT NULL
BEGIN
    SET @Sql = N'ALTER DATABASE ' + QUOTENAME(@DatabaseName) + N' SET SINGLE_USER WITH ROLLBACK IMMEDIATE';
    EXEC(@Sql);
END

SET @Sql = N'RESTORE DATABASE ' + QUOTENAME(@DatabaseName) + N'
FROM DISK = N''' + @BackupPath + N'''
WITH REPLACE,
MOVE N''' + REPLACE(@DataLogicalName, '''', '''''') + N''' TO N''/var/opt/mssql/data/' + @DatabaseName + N'.mdf'',
MOVE N''' + REPLACE(@LogLogicalName, '''', '''''') + N''' TO N''/var/opt/mssql/data/' + @DatabaseName + N'_log.ldf''';
EXEC(@Sql);

SET @Sql = N'ALTER DATABASE ' + QUOTENAME(@DatabaseName) + N' SET MULTI_USER';
EXEC(@Sql);

SELECT name AS restored_database, create_date FROM sys.databases WHERE name = @DatabaseName;
"@

$sql = $sql.TrimStart([char]0xFEFF)

$tempFile = [System.IO.Path]::GetTempFileName()
[System.IO.File]::WriteAllText($tempFile, $sql, [System.Text.UTF8Encoding]::new($false))

try {
    docker cp $tempFile "${ContainerName}:/tmp/restore-fujimart-db.sql" | Out-Null
    docker exec $ContainerName /opt/mssql-tools18/bin/sqlcmd `
        -S localhost `
        -U sa `
        -P $password `
        -C `
        -b `
        -i /tmp/restore-fujimart-db.sql

    if ($LASTEXITCODE -ne 0) {
        throw "sqlcmd restore failed with exit code $LASTEXITCODE."
    }

    docker exec $ContainerName /opt/mssql-tools18/bin/sqlcmd `
        -S localhost `
        -U sa `
        -P $password `
        -C `
        -b `
        -Q "IF DB_ID(N'$DatabaseName') IS NULL THROW 51001, 'Fujimart source database restore failed.', 1;"

    if ($LASTEXITCODE -ne 0) {
        throw "restore verification failed with exit code $LASTEXITCODE."
    }
} finally {
    Remove-Item -LiteralPath $tempFile -Force -ErrorAction SilentlyContinue
}
