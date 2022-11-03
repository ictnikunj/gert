<?php /*sleep(5);*/ ?><!doctype html>
<html lang="en">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, minimum-scale=1, initial-scale=1, user-scalable=yes">
      <title>A dummy page</title>
      <style>
        body {
          /*
          background-color: black;
          color: white;
          */
        }

        .row {
          display: flex;
          margin-left: -0.25rem;
          margin-right: -0.25rem;
        }

        .col {
          flex: 1;
          padding: 0 0.25rem;
        }

        .ropi-content-editor-dragging .row,
        .ropi-content-editor-editmode .row {
          margin: 0.5rem 0;
        }

        .ropi-content-editor-dragging .col,
        .ropi-content-editor-editmode .col {
          padding: 0 !important;
        }

        .preview-info {
          display: block;
          position: fixed;
          left: 0;
          bottom: 0;
          right: 0;
          background-color: red;
          color: white;
          font-weight: bold;
          opacity: 0;
          padding: 1rem;
          z-index: 1000000;
          animation: ropi-cms-preview-info 300ms ease;
          animation-delay: 300ms;
          animation-fill-mode: forwards;
        }

        .ropi-content-editor .preview-info {
          display: none;
        }

        @keyframes ropi-cms-preview-info {
          from {opacity: 0; transform: translateY(100%)}
          to {opacity: 1; transform: translateY(0)}
        }

        .ropi-content-editor-drop-marker:last-child {
            top: -8px;
        }

        .ropi-content-editor-drop-marker:first-child {
            top: 2px;
        }
      </style>
  </head>
  <body data-ropi-document-context='{"id": "123", "lang": "de", "page": "45"}' data-ropi-document-versions='[{"id":10,"published":0,"time":"1611753239","user":"Robert"},{"id":9,"published":1,"time":"1611633239","user":"Admin"}]'>
    <h1>
        <a href="<?php echo $_SERVER['REQUEST_URI']; ?>">Dummy Page</a>
    </h1>
    <div class="preview-info">Preview Mode</div>
    <header>
      <nav>
        <a href="#">Nav-Item-1</a>
        <a href="#">Nav-Item-2</a>
      </nav>
    </header>
    <div>
      <div>Content-Area</div>
      <div data-ropi-content-area="contentArea" data-ropi-content-area-allowed="headline">

      </div>
      A deeper nested area:
      <div>
        <div data-ropi-content-area="testarea" data-ropi-content-area-disallowed="headline">

        </div>
      </div>
    </div>
    <div>
      some static text
    </div>
    <div>
      <div>Another Content-Area</div>
      <div data-ropi-content-area="anotherContentArea" data-ropi-content-area-allowed="grid">
        <div class="row" data-ropi-content-element="grid" data-ropi-content-element-uuid="uuid123" data-ropi-content-element-creation-timestamp="123">
          <div class="col"
               data-ropi-content-area="col1">
            <h3 data-ropi-content-element="headline" data-ropi-content-element-uuid="uuid1" data-ropi-content-element-creation-timestamp="234" data-ropi-content-element-configuration='{"level": "3"}'>
              <div data-ropi-content-editable="text" data-ropi-content-editable-commands="italic,underline,justifyleft,justifycenter,justifyright,createlink,unlink">I am a <a href="https://www.google.de">headline</a></div>
            </h3>
            <div
              data-ropi-content-element="productlist"
              data-ropi-content-element-uuid="uuid2"
              data-ropi-content-element-creation-timestamp="567">
              <div>
                <div data-ropi-content-editable="text" data-ropi-content-editable-defaultParagraphSeparator="p">Produktliste</div>
              </div>
              <ul style="margin: 0;">
                <li>
                  Product A
                </li>
                <li>
                  Product B
                </li>
                <li>
                  Product C
                </li>
                <li>
                  Product D
                </li>
                <li>
                  Product E
                </li>
                <li>
                  Product F
                </li>
                <li>
                  Product G
                </li>
                <li>
                  Product H
                </li>
              </ul>
            </div>
          </div>
          <div class="col"
               data-ropi-content-area="col2"
               style="height: 100px;overflow:hidden;overflow-y: scroll;">
          </div>
        </div>
      </div>
    </div>
    <div>
      another<br />
      static<br />
      text<br />
      which<br />
      can<br />
      not<br />
      be<br />
      edited<br />
    </div>
  </body>
</html>
