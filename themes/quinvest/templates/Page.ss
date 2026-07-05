<!doctype html>
<html lang="$ContentLocale">
<head>
    <% base_tag %>
    <%-- Required meta tags --%>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    $MetaTags(false)

    <% include Favicons %>

    <title><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> | $SiteConfig.Title</title>

</head>
<body>
    <% include Header %>
        $Layout
    <% include Footer %>
</body>
</html>