(function() {
    var __ = window.wp.i18n.__;
    var { insert, applyFormat, toggleFormat } = window.wp.richText;
    var registerFormatType = window.wp.richText.registerFormatType;
    var { createElement, useState, useRef } = window.wp.element;
    var { RichTextToolbarButton } = window.wp.blockEditor;
    var { Popover, TextControl, Button } = window.wp.components;

    registerFormatType( 'wppy-nihao/rt', {
        title: __( 'Ruby Character', 'wppy-nihao' ),
        tagName: 'rt',
        className: null,
        edit: function( {isActive, value, onChange} ) {
            return createElement('rt', null);
        }
    } );

    registerFormatType( 'wppy-nihao/ruby', {
        title: __( '注音', 'wppy-nihao' ),
        tagName: 'ruby',
        className: null,
        edit: function ( props ) {
            var value = props.value;
            var isActive = props.isActive;
            var onChange = props.onChange;
            
            const [isPopoverOpen, setIsPopoverOpen] = useState(false);
            const [pinyinText, setPinyinText] = useState('');
            const buttonRef = useRef();

            function handleTogglePopover() {
                if (!isActive) {
                    const selectedText = value.text.substr(value.start, value.end - value.start);
                    setPinyinText(selectedText);
                    setIsPopoverOpen(!isPopoverOpen);
                } else {
                    onChange(toggleFormat(value, { type: 'wppy-nihao/ruby' }));
                }
            }

            function handleApplyPinyin() {
                if (pinyinText) {
                    const rubyEnd = value.end;
                    const rubyStart = value.start;
                    let newValue = insert(value, pinyinText, rubyEnd);
                    newValue.start = rubyStart;
                    newValue.end = rubyEnd + pinyinText.length;
                    newValue = applyFormat(newValue, {
                        type: 'wppy-nihao/ruby'
                    }, rubyStart, rubyEnd + pinyinText.length);
                    newValue = applyFormat(newValue, {
                        type: 'wppy-nihao/rt'
                    }, rubyEnd, rubyEnd + pinyinText.length);
                    onChange(newValue);
                }
                setIsPopoverOpen(false);
                setPinyinText('');
            }

            function handleCancel() {
                setIsPopoverOpen(false);
                setPinyinText('');
            }

            return createElement('div', { style: { position: 'relative' } }, [
                createElement(RichTextToolbarButton, {
                    key: 'button',
                    ref: buttonRef,
                    title: __( '注音', 'wppy-nihao' ),
                    onClick: handleTogglePopover,
                    icon: createElement('div', {
                         style: { 
                             width: '16px', 
                             height: '16px', 
                             margin: '2px 6px 0 2px', 
                             display: 'inline-block'
                         },
                         dangerouslySetInnerHTML: {
                             __html: '<svg viewBox="0 0 1024 1024" width="16" height="16" style="vertical-align: middle;" fill="currentColor"><path d="M288 595.2v243.2c0 44.8-12.8 70.4-32 83.2-38.4 12.8-83.2 19.2-121.6 19.2-6.4-32-12.8-57.6-25.6-83.2H192c12.8 0 12.8-6.4 12.8-12.8V620.8l-96 32-25.6-83.2c32-6.4 70.4-19.2 121.6-32V352H96V268.8h108.8V89.6h83.2v179.2h83.2v83.2H288V512l76.8-19.2 12.8 76.8-89.6 25.6z m652.8 19.2h-121.6v326.4h-89.6V614.4H608c-6.4 134.4-70.4 256-179.2 339.2-19.2-25.6-38.4-44.8-57.6-64 89.6-57.6 147.2-160 153.6-275.2H390.4V531.2h134.4V358.4H422.4V281.6h128c-19.2-57.6-44.8-108.8-76.8-160l76.8-32c32 51.2 64 102.4 83.2 153.6l-76.8 38.4h166.4c32-64 57.6-128 76.8-192l96 25.6c-25.6 57.6-51.2 108.8-83.2 160h108.8v83.2h-102.4v166.4h121.6v89.6z m-211.2-83.2V358.4H608v166.4h121.6z"></path></svg>'
                         }
                     }),
                    isActive: isActive,
                    className: 'toolbar-button-with-text wppy_nihao',
                }),
                isPopoverOpen && createElement(Popover, {
                    key: 'popover',
                    anchor: buttonRef.current,
                    placement: 'bottom-start',
                    onClose: handleCancel,
                    className: 'wppy-pinyin-popover'
                }, [
                    createElement('div', {
                        key: 'content',
                        style: { 
                            padding: '16px', 
                            minWidth: '200px',
                            backgroundColor: '#fff',
                            border: '1px solid #ddd',
                            borderRadius: '4px',
                            boxShadow: '0 2px 6px rgba(0,0,0,0.1)'
                        }
                    }, [
                        createElement(TextControl, {
                             key: 'input',
                             label: __( '输入拼音注音', 'wppy-nihao' ),
                             value: pinyinText,
                             onChange: setPinyinText,
                             placeholder: __( '请输入拼音...', 'wppy-nihao' ),
                             autoFocus: true,
                             __next40pxDefaultSize: true,
                             __nextHasNoMarginBottom: true,
                             onKeyDown: function(event) {
                                 if (event.key === 'Enter') {
                                     event.preventDefault();
                                     handleApplyPinyin();
                                 } else if (event.key === 'Escape') {
                                     event.preventDefault();
                                     handleCancel();
                                 }
                             }
                         }),
                        createElement('div', {
                             key: 'buttons',
                             style: { 
                                 marginTop: '12px', 
                                 display: 'flex', 
                                 justifyContent: 'flex-end',
                                 gap: '8px'
                             }
                         }, [
                             createElement(Button, {
                                 key: 'cancel',
                                 variant: 'tertiary',
                                 onClick: handleCancel,
                                 text: __( '取消', 'wppy-nihao' )
                             }),
                             createElement(Button, {
                                 key: 'apply',
                                 variant: 'primary',
                                 onClick: handleApplyPinyin,
                                 text: __( '应用', 'wppy-nihao' ),
                                 disabled: !pinyinText.trim()
                             })
                         ])
                    ])
                ])
            ]);
        }
    } );
})();

