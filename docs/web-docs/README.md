# Documentation site

The public documentation of LightLMS, as a static site.

## What it is

Plain HTML and one CSS file. No build step, no bundler, no framework. The site is published
to GitHub Pages by `.github/workflows/pages.yml`, which uploads `docs/web-docs/` as it is.

## The pages

| File | Page |
|---|---|
| `index.html` | Overview |
| `quick-start.html` | Quick start |
| `configuration.html` | Configuration and roles |
| `courses.html` | Courses and lessons |
| `grading.html` | Assignments, quizzes, and the gradebook |
| `certificates.html` | Certificates of completion |
| `notifications.html` | Notifications and preferences |
| `api.html` | REST API reference |
| `deployment.html` | Deployment with Docker |
| `contributing.html` | Contributing |

## The rules

- The sidebar and the footer are repeated in every file. There is no template engine, so
  a change to the navigation means editing every page.
- Colours come from the token block at the top of `assets/styles.css`. They are the same
  Ink Indigo values as the application, so the documentation looks like the product.
- The site has no build step and no dependency. It must keep working as plain files.

## Checking a change

The workflow checks the internal links before it publishes. To run the same check locally:

```bash
cd docs/web-docs
for page in *.html; do
    for link in $(grep -o 'href="[^"#]*\.html' "$page" | sed 's/href="//'); do
        [ -f "$link" ] || echo "broken link in $page: $link"
    done
done
```

## Enabling GitHub Pages

1. In the repository settings, open **Pages**.
2. Set the source to **GitHub Actions**.
3. Push a change that touches `docs/web-docs/`.

The workflow publishes the site and the URL appears in the Pages settings.
