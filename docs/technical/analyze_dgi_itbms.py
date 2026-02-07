#!/usr/bin/env python3
"""
Script para extraer y analizar validaciones ITBMS de la ficha técnica DGI Panamá
Busca específicamente reglas sobre error 2152 y validaciones de montos
"""

import PyPDF2
import re
import json
from typing import List, Dict, Any

def extract_pdf_text(pdf_path: str) -> str:
    """Extrae todo el texto del PDF"""
    try:
        with open(pdf_path, 'rb') as file:
            pdf_reader = PyPDF2.PdfReader(file)
            text = ""

            for page_num in range(len(pdf_reader.pages)):
                page = pdf_reader.pages[page_num]
                page_text = page.extract_text()
                text += f"\n=== PÁGINA {page_num + 1} ===\n{page_text}\n"

        return text
    except Exception as e:
        return f"Error extracting PDF: {str(e)}"

def find_itbms_validations(text: str) -> Dict[str, Any]:
    """Busca validaciones específicas sobre ITBMS y errores 2152"""

    # Patrones para buscar información relevante
    patterns = {
        'error_2152': r'2152.*?[Mm]onto.*?[Ii][Tt][Bb][Mm][Ss].*?inválido',
        'itbms_validation': r'[Ii][Tt][Bb][Mm][Ss].*?validaci[óo]n',
        'precio_item': r'precio.*?ítem.*?validaci[óo]n',
        'monto_invalido': r'monto.*?inválido.*?ítem',
        'total_validation': r'total.*?ítem.*?validaci[óo]n',
        'campos_totales': r'dTotRec|iPzPag|dVTotItems|dTotNeto',
        'formulas_itbms': r'[Ii][Tt][Bb][Mm][Ss]\s*=.*?\d+',
        'tasas_itbms': r'7%|10%|15%.*?[Ii][Tt][Bb][Mm][Ss]',
        'campos_xml': r'<d[A-Z][a-zA-Z]*>.*?</d[A-Z][a-zA-Z]*>',
        'validaciones_pac': r'[Pp][Aa][Cc].*?validaci[óo]n.*?error',
    }

    results = {}

    for pattern_name, pattern in patterns.items():
        matches = re.findall(pattern, text, re.IGNORECASE | re.DOTALL)
        results[pattern_name] = {
            'count': len(matches),
            'matches': matches[:10]  # Primeros 10 resultados
        }

    # Buscar secciones específicas sobre validaciones
    sections = []
    lines = text.split('\n')

    for i, line in enumerate(lines):
        # Buscar títulos de secciones relevantes
        if any(keyword in line.lower() for keyword in ['validación', 'itbms', 'error', 'monto', 'precio']):
            # Capturar contexto (5 líneas antes y después)
            start = max(0, i - 5)
            end = min(len(lines), i + 15)
            context = '\n'.join(lines[start:end])

            sections.append({
                'line_number': i + 1,
                'title': line.strip(),
                'context': context
            })

    results['relevant_sections'] = sections[:20]  # Primeras 20 secciones relevantes

    return results

def find_calculation_rules(text: str) -> List[Dict[str, Any]]:
    """Busca reglas específicas de cálculo ITBMS"""

    calculation_patterns = [
        r'precio\s*\*\s*tasa.*?itbms',
        r'base\s*\*\s*\d+%',
        r'subtotal\s*\+\s*itbms\s*=\s*total',
        r'dPrItem.*?dValITBMS.*?dValTotItem',
        r'precioUnitario.*?valorITBMS.*?valorTotal',
    ]

    rules = []
    for pattern in calculation_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE | re.DOTALL)
        for match in matches:
            rules.append({
                'pattern': pattern,
                'match': match,
                'type': 'calculation_rule'
            })

    return rules

def main():
    pdf_path = "public/4-Anexo-3-Ficha-Técnica-Factura-Electrónica-Proveedores-Autorización-Calificados-V1.0.pdf"

    print("🔍 Extrayendo texto de la ficha técnica DGI Panamá...")
    text = extract_pdf_text(pdf_path)

    if "Error" in text:
        print(f"❌ {text}")
        return

    print(f"✅ Texto extraído: {len(text)} caracteres")

    print("\n🔍 Buscando validaciones ITBMS específicas...")
    validations = find_itbms_validations(text)

    print("\n🔍 Buscando reglas de cálculo...")
    calculation_rules = find_calculation_rules(text)

    # Generar reporte
    report = {
        'pdf_info': {
            'path': pdf_path,
            'text_length': len(text),
            'analysis_date': '2025-10-15'
        },
        'itbms_validations': validations,
        'calculation_rules': calculation_rules,
        'summary': {
            'error_2152_mentions': validations.get('error_2152', {}).get('count', 0),
            'itbms_validation_mentions': validations.get('itbms_validation', {}).get('count', 0),
            'relevant_sections_found': len(validations.get('relevant_sections', [])),
            'calculation_rules_found': len(calculation_rules)
        }
    }

    # Guardar reporte
    with open('docs/technical/dgi_itbms_analysis_report.json', 'w', encoding='utf-8') as f:
        json.dump(report, f, indent=2, ensure_ascii=False)

    # Guardar texto completo para revisión manual
    with open('docs/technical/dgi_ficha_tecnica_full_text.txt', 'w', encoding='utf-8') as f:
        f.write(text)

    print("\n📊 RESUMEN DE ANÁLISIS:")
    print(f"  📄 Texto extraído: {len(text):,} caracteres")
    print(f"  🔍 Menciones error 2152: {report['summary']['error_2152_mentions']}")
    print(f"  📝 Validaciones ITBMS: {report['summary']['itbms_validation_mentions']}")
    print(f"  📋 Secciones relevantes: {report['summary']['relevant_sections_found']}")
    print(f"  🧮 Reglas de cálculo: {report['summary']['calculation_rules_found']}")

    print("\n✅ Análisis completado.")
    print("📁 Archivos generados:")
    print("   - docs/technical/dgi_itbms_analysis_report.json")
    print("   - docs/technical/dgi_ficha_tecnica_full_text.txt")

if __name__ == "__main__":
    main()
