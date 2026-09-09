# PowerShell script para reescribir archivos PHP como UTF-8 sin BOM
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    $text = Get-Content -Raw -Encoding Byte -Path $_.FullName
    # Reescribe como UTF8 sin BOM
    [System.IO.File]::WriteAllText($_.FullName, [System.Text.Encoding]::UTF8.GetString($text), New-Object System.Text.UTF8Encoding($false))
    Write-Output "Rewritten: $($_.FullName)"
}
