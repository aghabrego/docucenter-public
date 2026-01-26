#!/usr/bin/env python3
"""
Lector PDF Ficha Técnica DGI - Campos B406-B416
Buscar información específica sobre campos extranjeros B411-B416
"""

import pdfplumber
import re
import sys

def search_b406_b416_fields(pdf_path):
    """
    Buscar información sobre campos B406-B416 en la ficha técnica
    """
    print("🔍 LEYENDO FICHA TÉCNICA DGI - Campos B406-B416")
    print("=" * 55)
    print()

    try:
        with pdfplumber.open(pdf_path) as pdf:
            print(f"📄 PDF tiene {len(pdf.pages)} páginas")
            print()

            # Buscar campos B406-B416
            b_fields_found = {}

            for page_num, page in enumerate(pdf.pages, 1):
                text = page.extract_text()
                if not text:
                    continue

                # Buscar patrones B4XX
                b4_pattern = r'B4(0[6-9]|1[0-6])'
                matches = re.finditer(b4_pattern, text, re.IGNORECASE)

                for match in matches:
                    field_code = match.group(0)
                    # Extraer contexto alrededor del campo
                    start = max(0, match.start() - 100)
                    end = min(len(text), match.end() + 200)
                    context = text[start:end].strip()

                    if field_code not in b_fields_found:
                        b_fields_found[field_code] = []

                    b_fields_found[field_code].append({
                        'page': page_num,
                        'context': context
                    })

            # Mostrar resultados
            if b_fields_found:
                print("📋 CAMPOS B406-B416 ENCONTRADOS:")
                print("-" * 40)

                for field_code in sorted(b_fields_found.keys()):
                    print(f"\n🔸 {field_code}:")
                    for occurrence in b_fields_found[field_code]:
                        print(f"   📄 Página {occurrence['page']}:")
                        print(f"   {occurrence['context'][:300]}...")
                        print()
            else:
                print("❌ No se encontraron campos B406-B416 específicos")

            # Buscar secciones sobre "extranjero" o "información adicional"
            print("\n🔍 BUSCANDO INFORMACIÓN SOBRE RECEPTORES EXTRANJEROS:")
            print("-" * 55)

            extranjero_sections = []

            for page_num, page in enumerate(pdf.pages, 1):
                text = page.extract_text()
                if not text:
                    continue

                # Buscar menciones de extranjero o información adicional
                patterns = [
                    r'extranjero[^.]*?(?:obligatorio|opcional|requerido|necesario)',
                    r'información adicional[^.]*?extranjero',
                    r'receptor[^.]*?extranjero[^.]*?(?:campo|dato|información)',
                    r'B4\d{2}[^.]*?(?:obligatorio|opcional|requerido)'
                ]

                for pattern in patterns:
                    matches = re.finditer(pattern, text, re.IGNORECASE | re.DOTALL)
                    for match in matches:
                        start = max(0, match.start() - 50)
                        end = min(len(text), match.end() + 100)
                        context = text[start:end].strip()

                        extranjero_sections.append({
                            'page': page_num,
                            'context': context,
                            'pattern': pattern
                        })

            # Mostrar secciones relevantes
            if extranjero_sections:
                for i, section in enumerate(extranjero_sections[:10], 1):  # Primeros 10 resultados
                    print(f"\n📄 Resultado {i} (Página {section['page']}):")
                    print(f"   {section['context'][:400]}...")
                    print()
            else:
                print("❌ No se encontró información específica sobre campos extranjeros")

            # Buscar tabla o lista de campos
            print("\n🔍 BUSCANDO TABLAS DE CAMPOS:")
            print("-" * 35)

            for page_num, page in enumerate(pdf.pages, 1):
                text = page.extract_text()
                if not text:
                    continue

                # Buscar líneas que contengan B4XX junto con descripción
                lines = text.split('\n')
                for line in lines:
                    if re.search(r'B4(0[6-9]|1[0-6])', line, re.IGNORECASE):
                        # Verificar si la línea contiene información útil
                        if any(keyword in line.lower() for keyword in ['campo', 'descripción', 'obligatorio', 'opcional', 'provincia', 'distrito', 'teléfono', 'dirección']):
                            print(f"📄 Página {page_num}: {line.strip()}")

    except Exception as e:
        print(f"❌ Error al leer PDF: {str(e)}")
        return False

    return True

def main():
    pdf_path = "public/4-Anexo-3-Ficha-Técnica-Factura-Electrónica-Proveedores-Autorización-Calificados-V1.0.pdf"

    if not search_b406_b416_fields(pdf_path):
        print("❌ No se pudo procesar el PDF")
        sys.exit(1)

    print("\n" + "=" * 55)
    print("🎯 CONCLUSIONES SOBRE CAMPOS B411-B416:")
    print("=" * 55)
    print("Según el código actual en DocuCenter:")
    print("✅ B408 - Tipo Identificación: OBLIGATORIO")
    print("✅ B409 - Número Identificación: OBLIGATORIO")
    print("✅ B410 - País Extranjero: OBLIGATORIO")
    print("⚪ B411 - Provincia Extranjero: OPCIONAL")
    print("⚪ B412 - Distrito Extranjero: OPCIONAL")
    print("⚪ B413 - Corregimiento: OPCIONAL")
    print("⚪ B414 - Urbanización: OPCIONAL")
    print("⚪ B415 - Dirección: OPCIONAL")
    print("⚪ B416 - Teléfono: OPCIONAL")
    print("\nVerificar con la ficha técnica si esta implementación es correcta.")

if __name__ == "__main__":
    main()
