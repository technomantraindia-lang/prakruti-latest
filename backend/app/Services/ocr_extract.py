#!/usr/bin/env python3
"""
Cross-Platform Nutrition Label OCR Extraction Helper
Compatible with Python 3.9+ on Linux (RHEL/CloudLinux/Debian/Ubuntu) and Windows.
"""
import sys
import os

def main():
    if len(sys.argv) < 2:
        print("Usage: python ocr_extract.py <image_path>", file=sys.stderr)
        sys.exit(1)

    image_path = sys.argv[1]
    if not os.path.exists(image_path):
        print(f"Error: Image file not found: {image_path}", file=sys.stderr)
        sys.exit(1)

    extracted_text = ""

    # Strategy 1: Attempt pytesseract if installed
    try:
        import pytesseract
        from PIL import Image
        img = Image.open(image_path)
        extracted_text = pytesseract.image_to_string(img)
    except Exception:
        pass

    # Strategy 2: Attempt easyocr if installed
    if not extracted_text:
        try:
            import easyocr
            reader = easyocr.Reader(['en'], gpu=False)
            results = reader.readtext(image_path)
            extracted_text = "\n".join([r[1] for r in results])
        except Exception:
            pass

    if extracted_text and len(extracted_text.strip()) > 5:
        print("EXTRACTED_TEXT_START")
        print(extracted_text.strip())
        print("EXTRACTED_TEXT_END")
    else:
        # Graceful exit without error: indicates no OCR engine or unreadable image
        print("NO_TEXT_EXTRACTED")

if __name__ == '__main__':
    main()

