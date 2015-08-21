@ECHO OFF

FOR /F "delims=" %%i IN ('git rev-parse --abbrev-ref HEAD') DO SET CURRENT_BRANCH=%%i
ECHO Current Branch: %CURRENT_BRANCH%

IF CURRENT_BRANCH == 'Singapore-Production' (
	php phploy.php -s production
) ELSE (
 	ECHO  "You are not in Singapore-Production Branch"
)
pause