/* empty css                                      */document.addEventListener("DOMContentLoaded",function(){document.querySelector("#pressbooks-multi-institution-assign-users #bulk-action-selector-top").firstElementChild.textContent=Custom.defaultOptionText,document.querySelector("#pressbooks-multi-institution-assign-users #doaction").addEventListener("click",function(t){t.preventDefault();const e=document.querySelector("#pressbooks-multi-institution-assign-users #bulk-action-selector-top").value,o=document.querySelectorAll(".check-column input:checked");e!=="-1"&&o.length>0&&confirm(Custom.text)&&document.querySelector("#pressbooks-multi-institution-assign-users").submit()})});
//# sourceMappingURL=user-221d59e9.js.map
