class PinyinProEngine {
    constructor() {
        this.isLoaded = false;
        this.loadPinyinPro();
    }

    async loadPinyinPro() {
        if (typeof pinyinPro !== 'undefined') {
            this.isLoaded = true;
            return;
        }

        try {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/gh/zh-lx/pinyin-pro@latest/dist/pinyin-pro.js';
            script.onload = () => {
                this.isLoaded = true;
                console.log('PinyinPro engine loaded successfully');
            };
            script.onerror = () => {
                console.error('Failed to load PinyinPro engine');
            };
            document.head.appendChild(script);
        } catch (error) {
            console.error('Error loading PinyinPro:', error);
        }
    }

    async waitForLoad() {
        while (!this.isLoaded) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }

    async processText(text, options = {}) {
        await this.waitForLoad();
        
        if (!this.isLoaded || typeof pinyinPro === 'undefined') {
            throw new Error('PinyinPro engine not available');
        }

        const { pinyin } = pinyinPro;
        
        const defaultOptions = {
            toneType: 'symbol',
            type: 'string',
            pattern: 'pinyin'
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        if (finalOptions.toneType === 'number') {
            finalOptions.toneType = 'num';
        }
        
        return pinyin(text, finalOptions);
    }

    async processTextWithRuby(text, options = {}) {
        await this.waitForLoad();
        
        if (!this.isLoaded || typeof pinyinPro === 'undefined') {
            return text;
        }

        const { pinyin } = pinyinPro;
        
        const pinyinOptions = {
            toneType: options.toneStyle === 'number' ? 'num' : 'symbol',
            type: 'array',
            pattern: 'pinyin'
        };
        
        if (options.toneStyle === 'none') {
            pinyinOptions.toneType = 'none';
        }
        
        if (options.surnameMode) {
            pinyinOptions.mode = 'surname';
        }
        
        if (options.polyphoneMode === 'context') {
            pinyinOptions.mode = 'normal';
        } else if (options.polyphoneMode === 'heteronym') {
            pinyinOptions.multiple = true;
        }
        
        if (options.customRules && typeof options.customRules === 'object') {
            pinyinOptions.customPinyin = options.customRules;
        }

        try {
            const chars = text.split('');
            let result = '';
            let inRuby = false;

            for (let i = 0; i < chars.length; i++) {
                const char = chars[i];
                
                if (/[\u4e00-\u9fa5]/.test(char)) {
                    if (!inRuby) {
                        result += '<ruby>';
                        inRuby = true;
                    }
                    
                    let pinyinStr = this.getContextualPinyin(text, i, char, pinyinOptions);
                    
                    if (pinyinStr && pinyinStr !== char) {
                        result += `${char}<rp>(</rp><rt>${pinyinStr}</rt><rp>)</rp>`;
                    } else {
                        result += char;
                    }
                } else {
                    if (inRuby) {
                        result += '</ruby>';
                        inRuby = false;
                    }
                    result += char;
                }
            }
            
            if (inRuby) {
                result += '</ruby>';
            }
            
            return result;
        } catch (error) {
            console.error('Error processing text with PinyinPro:', error);
            return text;
        }
    }

    getContextualPinyin(text, position, char, pinyinOptions) {
        const contextRules = {
            '着': {
                '着急': 'zháo',
                '着火': 'zháo',
                '着凉': 'zháo',
                '着迷': 'zháo',
                '穿着': 'zhuó',
                '着装': 'zhuó',
                '着手': 'zhuó',
                '着眼': 'zhuó',
                '沿着': 'zhe',
                '顺着': 'zhe',
                '接着': 'zhe'
            },
            '行': {
                '才行': 'xíng',
                '不行': 'xíng',
                '可行': 'xíng',
                '进行': 'xíng',
                '执行': 'xíng',
                '实行': 'xíng',
                '举行': 'xíng',
                '流行': 'xíng',
                '银行': 'háng',
                '行业': 'háng',
                '同行': 'háng'
            },
            '了': {
                '了不起': 'liǎo',
                '了解': 'liǎo',
                '明了': 'liǎo',
                '知了': 'liǎo'
            }
        };
        
        if (contextRules[char]) {
            for (const [word, correctPinyin] of Object.entries(contextRules[char])) {
                const wordStart = position - text.substring(0, position).lastIndexOf(word.charAt(0));
                if (wordStart >= 0 && text.substring(position - wordStart, position - wordStart + word.length) === word) {
                    const charIndex = position - (position - wordStart);
                    if (word.charAt(charIndex) === char) {
                        return correctPinyin;
                    }
                }
                
                if (text.substring(position, position + word.length) === word && word.charAt(0) === char) {
                    return correctPinyin;
                }
                
                for (let j = 1; j < word.length; j++) {
                    if (position >= j && text.substring(position - j, position - j + word.length) === word) {
                        if (word.charAt(j) === char) {
                            return correctPinyin;
                        }
                    }
                }
            }
        }
        
        const { pinyin } = pinyinPro;
        const pinyinResult = pinyin(char, pinyinOptions);
        return Array.isArray(pinyinResult) ? pinyinResult[0] : pinyinResult;
    }

    async getMultiplePronunciations(char) {
        await this.waitForLoad();
        
        if (!this.isLoaded || typeof pinyinPro === 'undefined') {
            return [char];
        }

        try {
            const { pinyin } = pinyinPro;
            const result = pinyin(char, {
                type: 'array',
                pattern: 'pinyin',
                toneType: 'symbol'
            });
            
            return Array.isArray(result) ? result : [result];
        } catch (error) {
            console.error('Error getting multiple pronunciations:', error);
            return [char];
        }
    }
}

window.PinyinProEngine = PinyinProEngine;

if (typeof wpPinyinConfig !== 'undefined' && wpPinyinConfig.engine === 'pinyinpro') {
    window.pinyinEngine = new PinyinProEngine();
    
    document.addEventListener('DOMContentLoaded', function() {
        if (wpPinyinConfig.autoProcess) {
            processPageContent();
        }
    });
}

async function processPageContent() {
    if (!window.pinyinEngine) return;
    
    const markedElements = document.querySelectorAll('.wppy-frontend-process');
    if (markedElements.length > 0) {
        for (const element of markedElements) {
            await processMarkedElement(element);
        }
    } else {
        const contentElements = document.querySelectorAll('.entry-content, .post-content, .content');
        for (const element of contentElements) {
            await processElement(element);
        }
    }
}

async function processMarkedElement(element) {
    if (!window.pinyinEngine) return;
    
    if (element.innerHTML.includes('<ruby>')) {
        element.classList.remove('wppy-frontend-process');
        return;
    }
    
    const originalText = element.getAttribute('data-text') || element.textContent;
    
    if (originalText.includes('(') && originalText.includes(')')) {
        element.classList.remove('wppy-frontend-process');
        return;
    }
    
    try {
        const processedHTML = await window.pinyinEngine.processTextWithRuby(
                originalText,
                {
                    toneStyle: wpPinyinConfig.toneStyle || 'symbol',
                    polyphoneMode: wpPinyinConfig.polyphoneMode || 'context',
                    surnameMode: wpPinyinConfig.surnameMode,
                    customRules: wpPinyinConfig.customRules
                }
            );
        
        if (processedHTML !== originalText) {
            element.innerHTML = processedHTML;
            element.classList.remove('wppy-frontend-process');
            element.removeAttribute('data-text');
        }
    } catch (error) {
        console.error('Error processing marked element:', error);
    }
}

async function processElement(element) {
    if (!window.pinyinEngine) return;
    
    const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode: function(node) {
                if (node.parentNode.tagName === 'SCRIPT' || 
                    node.parentNode.tagName === 'STYLE' ||
                    node.parentNode.tagName === 'RT') {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        }
    );
    
    const textNodes = [];
    let node;
    while (node = walker.nextNode()) {
        if (node.textContent.trim() && /[\u4e00-\u9fa5]/.test(node.textContent)) {
            textNodes.push(node);
        }
    }
    
    for (const textNode of textNodes) {
        try {
            const processedHTML = await window.pinyinEngine.processTextWithRuby(
                textNode.textContent,
                {
                    toneStyle: wpPinyinConfig.toneStyle || 'symbol',
                    polyphoneMode: wpPinyinConfig.polyphoneMode || 'context',
                    surnameMode: wpPinyinConfig.surnameMode,
                    customRules: wpPinyinConfig.customRules
                }
            );
            
            if (processedHTML !== textNode.textContent) {
                const wrapper = document.createElement('span');
                wrapper.innerHTML = processedHTML;
                textNode.parentNode.replaceChild(wrapper, textNode);
            }
        } catch (error) {
            console.error('Error processing text node:', error);
        }
    }
}