<!doctype html>
<html lang="$ContentLocale">
<head>
    <% base_tag %>
    <%-- Required meta tags --%>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    $MetaTags(false)

    <% include Favicons %>
    <link rel="stylesheet" type="text/css" href="/_resources/themes/startup-theme/css/main.css">

    <title><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> | $SiteConfig.Title</title>
</head>
<body>
    <% include Header %>
        $Layout
    <% include Footer %>
    <script type="module" src="{$themedResourceURL('js/main.js')}" defer></script>
</body>
</html>
