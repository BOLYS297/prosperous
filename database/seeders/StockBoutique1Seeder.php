<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\Produit;
use App\Models\Boutique;
use Illuminate\Database\Seeder;

class StockBoutique1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $boutique = Boutique::where('nom', 'Boutique 1')->first();

        if (!$boutique) {
            $this->command->error('Boutique 1 non trouvée.');
            return;
        }

        // 1. Liste brute sous forme de collections d'entrées (permet la répétition des codes)
        $fichesInventaire = [
            // --- FICHE 1 (WhatsApp Image 2026-06-16 at 07.08.28.jpeg) ---
            ['code' => 'ECU0000', 'qte' => 196], ['code' => 'BAN0001', 'qte' => 10],
            ['code' => 'BLN0002', 'qte' => 167], ['code' => 'BLN0003', 'qte' => 275],
            ['code' => 'ECU0004', 'qte' => 37],  ['code' => 'ATC0005', 'qte' => 74],
            ['code' => 'FSB0006', 'qte' => 76],  ['code' => 'ECU0007', 'qte' => 100],
            ['code' => 'RDL0008', 'qte' => 100], ['code' => 'BLN0009', 'qte' => 11],
            ['code' => 'RDL0010', 'qte' => 9],   ['code' => 'BLN0011', 'qte' => 67],
            ['code' => 'BLN0012', 'qte' => 177], ['code' => 'RDL0013', 'qte' => 350],
            ['code' => 'BLN0014', 'qte' => 106], ['code' => 'RDL0015', 'qte' => 66],
            ['code' => 'BLN0016', 'qte' => 120], ['code' => 'BLN0017', 'qte' => 2],
            ['code' => 'BLN0018', 'qte' => 16],  ['code' => 'BLN0019', 'qte' => 125],
            ['code' => 'BLN0020', 'qte' => 162], ['code' => 'ECU0021', 'qte' => 49],
            ['code' => 'BLN0022', 'qte' => 123], ['code' => 'ECU0023', 'qte' => 103],
            ['code' => 'CPP0024', 'qte' => 45],  ['code' => 'CPP0025', 'qte' => 59],

            // --- FICHE 2 (anomalie.jpeg) ---
            ['code' => 'BDL0026', 'qte' => 32],  ['code' => 'AHS0027', 'qte' => 293],
            ['code' => 'ECU0028', 'qte' => 32],  ['code' => 'BLN0029', 'qte' => 62],
            ['code' => 'AHS0030', 'qte' => 57],  ['code' => 'BLN0031', 'qte' => 20],
            ['code' => 'BCC0032', 'qte' => 36],  ['code' => 'AHL0033', 'qte' => 5],
            ['code' => 'AHL0034', 'qte' => 56],  ['code' => 'AHL0035', 'qte' => 8],
            ['code' => 'AHL0036', 'qte' => 7],   ['code' => 'AHL0037', 'qte' => 34],
            ['code' => 'SCS0038', 'qte' => 30],  ['code' => 'AHL0039', 'qte' => 128],
            ['code' => 'BLN0040', 'qte' => 253], ['code' => 'YLB0041', 'qte' => 21],
            ['code' => 'HLT0042', 'qte' => 1],   ['code' => 'HLE0043', 'qte' => 2],
            ['code' => 'HLS0044', 'qte' => 1],   ['code' => 'BLN0045', 'qte' => 23],
            ['code' => 'RDL0046', 'qte' => 12],  ['code' => 'BLN0047', 'qte' => 90],
            ['code' => 'GNT0048', 'qte' => 74],  ['code' => 'GNT0049', 'qte' => 66],
            ['code' => 'GNT0050', 'qte' => 6],   ['code' => 'BNT0051', 'qte' => 110],
            ['code' => 'BLN0052', 'qte' => 10],

            // --- FICHE 3 (WhatsApp Image 2026-06-16 at 07.09.56.jpeg) ---
            ['code' => 'BCN0053', 'qte' => 16],  ['code' => 'GNC0054', 'qte' => 20],
            ['code' => 'GNT0055', 'qte' => 2],   ['code' => 'GNT0056', 'qte' => 28],
            ['code' => 'RTS0057', 'qte' => 6],   ['code' => 'RTS0058', 'qte' => 8],
            ['code' => 'RTS0059', 'qte' => 4],   ['code' => 'LVT0060', 'qte' => 34],
            ['code' => 'DPC0061', 'qte' => 30],  ['code' => 'DPC0062', 'qte' => 23],
            ['code' => 'DPB0063', 'qte' => 4],   ['code' => 'DPT0064', 'qte' => 4],
            ['code' => 'DPN0065', 'qte' => 3],   ['code' => 'AGB0066', 'qte' => 18],
            ['code' => 'AGB0067', 'qte' => 7],   ['code' => 'AAC0068', 'qte' => 25],
            ['code' => 'AAG0069', 'qte' => 22],  ['code' => 'AAT0070', 'qte' => 2],
            ['code' => 'AAB0071', 'qte' => 3],   ['code' => 'ADC0072', 'qte' => 8],
            ['code' => 'AAC0073', 'qte' => 2],   ['code' => 'AAH0074', 'qte' => 1],
            ['code' => 'BCV0075', 'qte' => 1],   ['code' => 'LVT0076', 'qte' => 30],
            ['code' => 'LVT0077', 'qte' => 107],

            // --- FICHE 4 (WhatsApp Image 2026-06-16 at 10.12.36.jpeg) ---
            ['code' => 'MDF0078', 'qte' => 111], ['code' => 'MDB0079', 'qte' => 10],
            ['code' => 'MDG0080', 'qte' => 4],   ['code' => 'LSM0081', 'qte' => 8],
            ['code' => 'HDS0082', 'qte' => 2],   ['code' => 'HAY0083', 'qte' => 2],
            ['code' => 'HYP0084', 'qte' => 2],   ['code' => 'HDD0085', 'qte' => 1],
            ['code' => 'SAR0086', 'qte' => 2],   ['code' => 'SAJ0087', 'qte' => 4],
            ['code' => 'SPB0088', 'qte' => 3],   ['code' => 'HDF0089', 'qte' => 0],
            ['code' => 'IAG0090', 'qte' => 2],   ['code' => 'BTS0091', 'qte' => 7],
            ['code' => 'TDC0092', 'qte' => 100], ['code' => 'CCC0093', 'qte' => 18],
            ['code' => 'CCJ0094', 'qte' => 5],   ['code' => 'CCT0094', 'qte' => 8],
            ['code' => 'CBT0095', 'qte' => 4],   ['code' => 'CCB0096', 'qte' => 2],
            ['code' => 'CCB0097', 'qte' => 2],   ['code' => 'CCB0098', 'qte' => 12],
            ['code' => 'CCT0099', 'qte' => 1],   ['code' => 'CDB0100', 'qte' => 20],
            ['code' => 'VWD0101', 'qte' => 4],   ['code' => 'VSM0102', 'qte' => 3],

            // --- FICHE 5 (IMG-20260616-WA0037.jpg) ---
            ['code' => 'RGB0103', 'qte' => 41],  ['code' => 'RGB0104', 'qte' => 80],
            ['code' => 'SGB0105', 'qte' => 6],   ['code' => 'VBQ0106', 'qte' => 3],
            ['code' => 'VBT0107', 'qte' => 1],   ['code' => 'SBY0108', 'qte' => 24],
            ['code' => 'SBS0109', 'qte' => 1],   ['code' => 'SCD0110', 'qte' => 4],
            ['code' => 'HML0111', 'qte' => 3],   ['code' => 'HNJ0112', 'qte' => 1],
            ['code' => 'SDX0113', 'qte' => 133], ['code' => 'STS0114', 'qte' => 197],
            ['code' => 'SGS0115', 'qte' => 38],  ['code' => 'PCD0116', 'qte' => 10],
            ['code' => 'PTS0117', 'qte' => 22],  ['code' => 'PTS0118', 'qte' => 6],
            ['code' => 'PTS0119', 'qte' => 1],   ['code' => 'PCO0120', 'qte' => 18],
            ['code' => 'PDG0121', 'qte' => 30],  ['code' => 'LRS0122', 'qte' => 30],
            ['code' => 'PAC0123', 'qte' => 1],   ['code' => 'TCB0124', 'qte' => 13],
            ['code' => 'PAG0125', 'qte' => 8],   ['code' => 'TCD0126', 'qte' => 2],
            ['code' => 'TCG0127', 'qte' => 4],   ['code' => 'PAT0128', 'qte' => 9],
            ['code' => 'TCG0129', 'qte' => 1],

            // --- FICHE 6 (WhatsApp Image 2026-06-16 at 23.44.30.jpeg) ---
            ['code' => 'TBM0130', 'qte' => 1],   ['code' => 'PFB0131', 'qte' => 2],
            ['code' => 'PDF0132', 'qte' => 2],   ['code' => 'PFT0133', 'qte' => 3],
            ['code' => 'PAB0134', 'qte' => 11],  ['code' => 'PAW0135', 'qte' => 10],
            ['code' => 'PNC0136', 'qte' => 5],   ['code' => 'PNL0137', 'qte' => 8],
            ['code' => 'PNL0138', 'qte' => 3],   ['code' => 'PNL0139', 'qte' => 1],
            ['code' => 'PNL0140', 'qte' => 3],   ['code' => 'PNL0141', 'qte' => 5],
            ['code' => 'GGM0142', 'qte' => 6],   ['code' => 'GPM0143', 'qte' => 9],
            ['code' => 'RLT0144', 'qte' => 328], ['code' => 'RLT0145', 'qte' => 198],
            ['code' => 'RLT0146', 'qte' => 12],  ['code' => 'RLT0147', 'qte' => 16],
            ['code' => 'RLT0148', 'qte' => 64],  ['code' => 'RLT0149', 'qte' => 30],
            ['code' => 'RLT0150', 'qte' => 28],  ['code' => 'RLT0151', 'qte' => 19],
            ['code' => 'RLT0152', 'qte' => 8],   ['code' => 'SGG0153', 'qte' => 4],
            ['code' => 'PLK0154', 'qte' => 1],

            // --- FICHE 7 (WhatsApp Image 2026-06-16 at 23.44.45.jpeg) ---
            ['code' => 'PFG0155', 'qte' => 9],   ['code' => 'ARN0156', 'qte' => 23],
            ['code' => 'BDG0157', 'qte' => 59],  ['code' => 'BDG0158', 'qte' => 13],
            ['code' => 'PTC0159', 'qte' => 9],   ['code' => 'GSC0160', 'qte' => 3],
            ['code' => 'CHA0161', 'qte' => 3],   ['code' => 'PMA0162', 'qte' => 15],
            ['code' => 'PMA0163', 'qte' => 13],  ['code' => 'PMA0164', 'qte' => 8],
            ['code' => 'GSP0165', 'qte' => 16],  ['code' => 'GST0166', 'qte' => 13],
            ['code' => 'CHM0167', 'qte' => 6],   ['code' => 'CDT0167', 'qte' => 64],
            ['code' => 'CDF0168', 'qte' => 8],   ['code' => 'CAS0169', 'qte' => 8],
            ['code' => 'VDF0170', 'qte' => 10],  ['code' => 'PDC0171', 'qte' => 4],
            ['code' => 'PDT0172', 'qte' => 2],   ['code' => 'SPT0173', 'qte' => 36],
            ['code' => 'SPC0174', 'qte' => 20],  ['code' => 'ABJ0174', 'qte' => 11],
            ['code' => 'ABS0175', 'qte' => 12],  ['code' => 'ABI0176', 'qte' => 2],
            ['code' => 'PDQ0177', 'qte' => 8],   ['code' => 'PDS0178', 'qte' => 1],

            // --- FICHE 8 (WhatsApp Image 2026-06-17 at 13.37.36.jpeg) ---
            ['code' => 'ACL0179', 'qte' => 140], ['code' => 'AFS0180', 'qte' => 110],
            ['code' => 'RDB0181', 'qte' => 132], ['code' => 'CAA0182', 'qte' => 13],
            ['code' => 'CHA0183', 'qte' => 10],  ['code' => 'CHA0184', 'qte' => 13],
            ['code' => 'PST0184', 'qte' => 13],  ['code' => 'BBY0185', 'qte' => 12],
            ['code' => 'PBB0185', 'qte' => 2],   ['code' => 'PCD0186', 'qte' => 7],
            ['code' => 'PBS0187', 'qte' => 1],   ['code' => 'PBS0188', 'qte' => 6],
            ['code' => 'PTV0189', 'qte' => 9],   ['code' => 'PCG0190', 'qte' => 10],
            ['code' => 'PCG0191', 'qte' => 7],   ['code' => 'BDC0191', 'qte' => 23],
            ['code' => 'BCB0192', 'qte' => 22],  ['code' => 'CMT0193', 'qte' => 5],
            ['code' => 'PFS0194', 'qte' => 2],   ['code' => 'CCH0195', 'qte' => 2],
            ['code' => 'CDA0196', 'qte' => 100], ['code' => 'AL0197',  'qte' => 89],
            ['code' => 'CHA0198', 'qte' => 5],   ['code' => 'PDF0199', 'qte' => 7],
            ['code' => 'PDF0200', 'qte' => 1],   ['code' => 'PDF0201', 'qte' => 1],
            ['code' => 'PPA0202', 'qte' => 2],

            // --- FICHE 9 (WhatsApp Image 2026-06-17 at 13.38.04.jpeg) ---
            ['code' => 'PJC0203', 'qte' => 8],   ['code' => 'PJC0204', 'qte' => 2],
            ['code' => 'CDI0205', 'qte' => 11],  ['code' => 'CDI0206', 'qte' => 1],
            ['code' => 'DCB0207', 'qte' => 7],   ['code' => 'DCT0208', 'qte' => 8],
            ['code' => 'PJC0209', 'qte' => 5],   ['code' => 'PJB0210', 'qte' => 4],
            ['code' => 'PPG0211', 'qte' => 5],   ['code' => 'DSB0212', 'qte' => 4],
            ['code' => 'DST0213', 'qte' => 3],   ['code' => 'DSP0214', 'qte' => 2],
            ['code' => 'BDA0215', 'qte' => 20],  ['code' => 'BAS0216', 'qte' => 22],
            ['code' => 'BTI0217', 'qte' => 1],   ['code' => 'RLT0218', 'qte' => 3],
            ['code' => 'MCG0219', 'qte' => 2],   ['code' => 'RLT0220', 'qte' => 11],
            ['code' => 'RLT0221', 'qte' => 1],   ['code' => 'PEG0222', 'qte' => 1],
            ['code' => 'PLC0223', 'qte' => 2],   ['code' => 'PHT0224', 'qte' => 4],
            ['code' => 'PHG0225', 'qte' => 2],   ['code' => 'PHB0226', 'qte' => 4],
            ['code' => 'PDG0227', 'qte' => 3],   ['code' => 'PPC0228', 'qte' => 3],
            ['code' => 'CRC0229', 'qte' => 40],

            ['code' => 'ANL0270', 'qte' => 2],
            ['code' => 'AHL0271', 'qte' => 35],
            ['code' => 'ADH500', 'qte' => 5],
            ['code' => 'AHL0272', 'qte' => 2],
            ['code' => 'CAB0273', 'qte' => 1],
            ['code' => 'BAB0274', 'qte' => 18],
            ['code' => 'PFS501', 'qte' => 1],
            ['code' => 'LTV502', 'qte' => 80],
            ['code' => 'CAP0275', 'qte' => 1],
            ['code' => 'PDH503', 'qte' => 1],
            ['code' => 'PDH0276', 'qte' => 4],
            ['code' => 'PPP504', 'qte' => 3],
            ['code' => 'PDB0277', 'qte' => 2],
            ['code' => 'DTS0278', 'qte' => 1],
            ['code' => 'PFS0279', 'qte' => 10],
            ['code' => 'PDF505', 'qte' => 14],
            ['code' => 'AVC0280', 'qte' => 19],
            ['code' => 'APP0281', 'qte' => 1],
            ['code' => 'TSP0282', 'qte' => 53],
            ['code' => 'ADH506', 'qte' => 11],
            ['code' => 'TDF0283', 'qte' => 35],
            ['code' => 'TDF0284', 'qte' => 12],
            ['code' => 'CDF0285', 'qte' => 22],
            ['code' => 'CDT0286', 'qte' => 39],
            ['code' => 'CDT0287', 'qte' => 6],
            ['code' => 'CDY0288', 'qte' => 18],
            ['code' => 'CDT0289', 'qte' => 3],
            ['code' => 'ASG0247', 'qte' => 7],
            ['code' => 'PAB507', 'qte' => 2],
            ['code' => 'MCL0248', 'qte' => 7],
            ['code' => 'ASG0249', 'qte' => 1],
            ['code' => 'GCT0250', 'qte' => 5],
            ['code' => 'CAP0251', 'qte' => 7],
            ['code' => 'CAP0252', 'qte' => 3],
            ['code' => 'CAP0253', 'qte' => 3],
            ['code' => 'CAP0254', 'qte' => 2],
            ['code' => 'CAP0255', 'qte' => 5],
            ['code' => 'CAP0256', 'qte' => 1],
            ['code' => 'CAP0257', 'qte' => 1],
            ['code' => 'CAP0258', 'qte' => 1],
            ['code' => 'CAP0259', 'qte' => 3],
            ['code' => 'CNG0260', 'qte' => 4],
            ['code' => 'APP0261', 'qte' => 5],
            ['code' => 'TDT0262', 'qte' => 4],
            ['code' => 'AAC0263', 'qte' => 3],
            ['code' => 'DIL0264', 'qte' => 14],
            ['code' => 'CAD0265', 'qte' => 1],
            ['code' => 'CAD0266', 'qte' => 1],
            ['code' => 'CAD0267', 'qte' => 4],
            ['code' => 'CAD0268', 'qte' => 1],
            ['code' => 'CCG0269', 'qte' => 2],
            ['code' => 'MAC0230', 'qte' => 1],
            ['code' => 'BDC508', 'qte' => 12],
            ['code' => 'BDC509', 'qte' => 60],
            ['code' => 'PDH0231', 'qte' => 1],
            ['code' => 'PFS0232', 'qte' => 3],
            ['code' => 'CHA510', 'qte' => 1],
            ['code' => 'SOU512', 'qte' => 5],
            ['code' => 'SBY0233', 'qte' => 1],
            ['code' => 'MDA513', 'qte' => 10],
            ['code' => 'PGT0234', 'qte' => 1],
            ['code' => 'PAC0235', 'qte' => 2],
            ['code' => 'PPC0236', 'qte' => 4],
            ['code' => 'GPC0237', 'qte' => 1],
            ['code' => 'JDG0238', 'qte' => 4],
            ['code' => 'CCC514', 'qte' => 4],
            ['code' => 'CCB515', 'qte' => 3],
            ['code' => 'PRG0239', 'qte' => 2],
            ['code' => 'PPP0240', 'qte' => 4],
            ['code' => 'ADD0241', 'qte' => 3],
            ['code' => 'CCB516', 'qte' => 1],
            ['code' => 'BBS0242', 'qte' => 6],
            ['code' => 'BCG0243', 'qte' => 1],
            ['code' => 'BCG0244', 'qte' => 5],
            ['code' => 'CSM0245', 'qte' => 1],
            ['code' => 'CCC0245', 'qte' => 2],
            ['code' => 'MDV0246', 'qte' => 3],

            // --- Quantités de Stock initial des nouvelles fiches ---
            ['code' => 'ASG0247', 'qte' => 7],   ['code' => 'MCL0248', 'qte' => 7],
            ['code' => 'ASG0249', 'qte' => 1],   ['code' => 'GCT0250', 'qte' => 5],
            ['code' => 'CAP0251', 'qte' => 7],   ['code' => 'CAP0252', 'qte' => 3],
            ['code' => 'CAP0253', 'qte' => 3],   ['code' => 'CAP0254', 'qte' => 2],
            ['code' => 'CAP0255', 'qte' => 5],   ['code' => 'CAP0256', 'qte' => 1],
            ['code' => 'CAP0257', 'qte' => 1],   ['code' => 'CAP0258', 'qte' => 1],
            ['code' => 'CAP0259', 'qte' => 3],   ['code' => 'CNG0260', 'qte' => 4],
            ['code' => 'APP0261', 'qte' => 5],   ['code' => 'TDT0262', 'qte' => 4],
            ['code' => 'AAC0263', 'qte' => 3],   ['code' => 'DIL0264', 'qte' => 14],
            ['code' => 'CAD0265', 'qte' => 1],   ['code' => 'CAD0266', 'qte' => 1],
            ['code' => 'CAD0267', 'qte' => 4],   ['code' => 'CAD0268', 'qte' => 1],
            ['code' => 'CCG0269', 'qte' => 2],   ['code' => 'ANL0270', 'qte' => 2],
            ['code' => 'AHL0271', 'qte' => 35],  ['code' => 'AHL0272', 'qte' => 2],
            ['code' => 'CAB0273', 'qte' => 1],   ['code' => 'BAS0274', 'qte' => 15],
            ['code' => 'CAP0275', 'qte' => 1],   ['code' => 'PDH0276', 'qte' => 4],
            ['code' => 'PDB0277', 'qte' => 2],   ['code' => 'DFS0278', 'qte' => 1],
            ['code' => 'PFS0279', 'qte' => 10],  ['code' => 'AVC0280', 'qte' => 19],
            ['code' => 'APP0281', 'qte' => 1],   ['code' => 'TSP0282', 'qte' => 53],
            ['code' => 'TDF0283', 'qte' => 35],  ['code' => 'TDF0284', 'qte' => 12],
            ['code' => 'CDF0285', 'qte' => 22],  ['code' => 'CDT0286', 'qte' => 39],
            ['code' => 'CDT0287', 'qte' => 6],   ['code' => 'CDY0288', 'qte' => 18],
            ['code' => 'CDT0289', 'qte' => 3],   ['code' => 'CDC0290', 'qte' => 3],
            ['code' => 'CDF0291', 'qte' => 12],  ['code' => 'CDB0292', 'qte' => 7],
            ['code' => 'CDC0293', 'qte' => 30],  ['code' => 'CDM0294', 'qte' => 59],
            ['code' => 'RPB0295', 'qte' => 54],  ['code' => 'RPB0296', 'qte' => 52],
            ['code' => 'RDF0297', 'qte' => 21],  ['code' => 'CHA0298', 'qte' => 50],
            ['code' => 'CHA0299', 'qte' => 50],  ['code' => 'GBA0300', 'qte' => 3],
            ['code' => 'PBC0301', 'qte' => 1],   ['code' => 'CDB0302', 'qte' => 2],
            ['code' => 'CDB0303', 'qte' => 8],   ['code' => 'CBN0304', 'qte' => 224],
            ['code' => 'JNT0305', 'qte' => 2],   ['code' => 'JTY0306', 'qte' => 2],
            ['code' => 'FAT0307', 'qte' => 2],   ['code' => 'FAB0308', 'qte' => 2],
            ['code' => 'MAC0230', 'qte' => 1],   ['code' => 'PDH0231', 'qte' => 1],
            ['code' => 'PFS0232', 'qte' => 3],   ['code' => 'SBY0233', 'qte' => 1],
            ['code' => 'PGT0234', 'qte' => 1],   ['code' => 'PAC0235', 'qte' => 2],
            ['code' => 'PPC0236', 'qte' => 4],   ['code' => 'GPC0237', 'qte' => 1],
            ['code' => 'JDG0238', 'qte' => 4],   ['code' => 'PRG0239', 'qte' => 2],
            ['code' => 'PPP0240', 'qte' => 4],   ['code' => 'ADD0241', 'qte' => 3],
            ['code' => 'BBJ0242', 'qte' => 6],   ['code' => 'BCG0243', 'qte' => 1],
            ['code' => 'BCG0244', 'qte' => 5],   ['code' => 'CSM0245', 'qte' => 1],
            ['code' => 'CCC0246', 'qte' => 2],   ['code' => 'MDV0246', 'qte' => 3],
            ['code' => 'CAH0309', 'qte' => 28],  ['code' => 'CAA0310', 'qte' => 23],
            ['code' => 'GDN0311', 'qte' => 8],   ['code' => 'CSS0312', 'qte' => 14],
            ['code' => 'CSG0313', 'qte' => 7],   ['code' => 'CSB0314', 'qte' => 3],
            ['code' => 'ADP0315', 'qte' => 64],  ['code' => 'LCB0316', 'qte' => 1],
            ['code' => 'BPP0317', 'qte' => 48],  ['code' => 'GDO0318', 'qte' => 7],
            ['code' => 'PPG0319', 'qte' => 112],  ['code' => 'TCM0319', 'qte' => 7],
            ['code' => 'TCM0320', 'qte' => 10],  ['code' => 'RDD0321', 'qte' => 11],
            ['code' => 'RDD0322', 'qte' => 6],   ['code' => 'RFB0323', 'qte' => 31],
            ['code' => 'CHB0324', 'qte' => 8],   ['code' => 'JCD0324', 'qte' => 50],
            ['code' => 'JCY0325', 'qte' => 50],  ['code' => 'DTT0326', 'qte' => 130],
            ['code' => 'JCD0327', 'qte' => 8],   ['code' => 'JCD0328', 'qte' => 29],
            ['code' => 'JCB0329', 'qte' => 97],  ['code' => 'JCD0330', 'qte' => 17],
            ['code' => 'JCB0331', 'qte' => 20],  ['code' => 'GDB0332', 'qte' => 1],
            ['code' => 'JDC0333', 'qte' => 25],  ['code' => 'BLN0334', 'qte' => 99],
            ['code' => 'BLN0335', 'qte' => 180],
        ];

        // 2. Étape de fusion dynamique des doublons en PHP
        $stocksCumules = [];

        foreach ($fichesInventaire as $item) {
            $code = $item['code'];
            $qte = $item['qte'];

            if (isset($stocksCumules[$code])) {
                // Si la référence existe déjà, on additionne la nouvelle quantité
                $stocksCumules[$code] += $qte;
            } else {
                // Sinon, on initialise la référence dans le tableau de cumul
                $stocksCumules[$code] = $qte;
            }
        }

        // 3. Insertion propre et synchronisation finale dans la base de données
        foreach ($stocksCumules as $reference => $quantite) {
            $produit = Produit::where('reference', $reference)->first();

            if ($produit) {
                Stock::updateOrCreate(
                    [
                        'boutique_id' => $boutique->id,
                        'produit_id'  => $produit->id
                    ],
                    [
                        'quantite'    => $quantite
                    ]
                );
            }
        }

        $this->command->info('✅ Base de données synchronisée : les doublons ont été combinés dynamiquement avec succès !');
    }
}
